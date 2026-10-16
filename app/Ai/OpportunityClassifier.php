<?php

namespace App\Ai;

use App\Ai\Agents\ListingReviewer;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Throwable;

class OpportunityClassifier
{
    // Set after a billing, key or permission error, so one run does not repeat a call that cannot work.
    private static ?string $offReason = null;

    public function enabled(): bool
    {
        return (bool) config('importer.ai.enabled') && filled(config('ai.providers.'.$this->provider().'.key')) && ! self::$offReason;
    }

    public function provider(): string
    {
        return (string) config('importer.ai.provider');
    }

    public function model(string $task): ?string
    {
        return config('importer.ai.models.'.$this->provider().'.'.$task);
    }

    public function label(): string
    {
        return ['openai' => 'OpenAI', 'anthropic' => 'Claude', 'openrouter' => 'OpenRouter'][$this->provider()] ?? $this->provider();
    }

    public function keyName(): string
    {
        return strtoupper($this->provider()).'_API_KEY';
    }

    public static function offReason(): ?string
    {
        return self::$offReason;
    }

    public static function resetOffReason(): void
    {
        self::$offReason = null;
    }

    // Returns the AI reading of a listing, or null when AI is off or the call fails, so the keyword rules stay in place.
    public function classify(array $data, array $fields): ?array
    {
        if (! $this->enabled()) { return null; }
        try {
            $response = (new ListingReviewer($fields))->prompt($this->listing($data), provider: $this->provider(), model: $this->model('review'));
        } catch (Throwable $e) {
            $this->stopOn($e);
            Log::warning('Opportunity classification failed; keyword rules were used.', ['error' => self::$offReason ?? Str::limit($e->getMessage(), 300)]);
            return null;
        }
        $review = $response->toArray();
        return in_array($review['field'] ?? null, $fields, true) ? [...$review, 'model' => $response->meta->model ?? $this->model('review')] : null;
    }

    private function stopOn(Throwable $e): void
    {
        $request = $e instanceof RequestException ? $e : $e->getPrevious();
        $response = $request instanceof RequestException ? $request->response : null;
        if ($e instanceof InsufficientCreditsException || in_array($response?->status(), [400, 401, 403], true) || $response?->json('error.code') === 'insufficient_quota') {
            self::$offReason = Str::limit($response?->json('error.message') ?? $e->getMessage(), 200);
        }
    }

    private function listing(array $data): string
    {
        return implode("\n", [
            'Title: '.$data['title'],
            'Employer: '.$data['employer'],
            'Location: '.($data['location'] ?: 'not given'),
            'Work type: '.$data['work_type'],
            'Employment types: '.(implode(', ', $data['employment_types'] ?? []) ?: 'not given'),
            '',
            'Description:',
            $data['description'],
        ]);
    }
}
