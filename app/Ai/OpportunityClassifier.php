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

    // Reads listings in batches and returns the AI answer per key of $listings. Listings left out
    // (AI off, a failed call or a missing answer) keep the keyword rules.
    public function classifyMany(array $listings, array $fields, array $skills = []): array
    {
        $reviews = [];
        foreach (array_chunk($listings, max(1, (int) config('importer.ai.batch_size', 10)), true) as $chunk) {
            if (! $this->enabled()) { break; }
            $keys = array_keys($chunk);
            $prompt = implode("\n\n", array_map(fn ($key, $data) => 'Listing '.(array_search($key, $keys, true) + 1)."\n".$this->listing($data), $keys, $chunk));
            try {
                $response = (new ListingReviewer($fields, $skills))->prompt($prompt, provider: $this->provider(), model: $this->model('review'));
            } catch (Throwable $e) {
                $this->stopOn($e);
                Log::warning('Opportunity classification failed; keyword rules were used.', ['error' => self::$offReason ?? Str::limit($e->getMessage(), 300)]);
                continue;
            }
            $model = $response->meta->model ?? $this->model('review');
            foreach ($response->toArray()['listings'] ?? [] as $review) {
                $key = $keys[($review['id'] ?? 0) - 1] ?? null;
                if ($key === null || isset($reviews[$key]) || ! in_array($review['field'] ?? null, $fields, true)) { continue; }
                unset($review['id']);
                $reviews[$key] = [...$review, 'model' => $model];
            }
        }
        return $reviews;
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
            // The start of a listing is enough to sort it.
            Str::limit($data['description'], 12000),
        ]);
    }
}
