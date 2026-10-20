<?php

namespace App\Models;

use App\Support\CvPdf;
use App\Support\CvTemplates;
use App\Support\LanguageGuess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// One of a candidate's resumes: an uploaded PDF, an export of their profile, or an AI translation of another resume.
class Resume extends Model
{
    public const LIMIT = 10;

    protected $fillable = [
        'candidate_id', 'translated_from_id', 'name', 'locale', 'source', 'status', 'style', 'path', 'file_name',
        'content', 'is_main', 'error', 'model', 'input_tokens', 'output_tokens', 'generations', 'credits_used',
    ];

    protected $hidden = ['path'];

    protected $casts = ['content' => 'array', 'style' => 'array', 'is_main' => 'boolean'];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function translatedFrom()
    {
        return $this->belongsTo(Resume::class, 'translated_from_id');
    }

    public function hasFile(): bool
    {
        return $this->path && Storage::disk('local')->exists($this->path);
    }

    // Uploaded PDFs can't be edited; the others are laid out from their content.
    public function isEditable(): bool
    {
        return $this->source !== 'uploaded';
    }

    public function makeMain(): void
    {
        DB::transaction(function () {
            Candidate::whereKey($this->candidate_id)->lockForUpdate()->first();
            static::where('candidate_id', $this->candidate_id)->whereKeyNot($this->id)->where('is_main', true)->update(['is_main' => false]);
            $this->update(['is_main' => true]);
        });
    }

    // Prints the content with its theme and keeps the new PDF in place of the old one.
    public function render(): void
    {
        $old = $this->path;
        $path = 'resumes/'.Str::random(40).'.pdf';
        Storage::disk('local')->put($path, app(CvPdf::class)->pdf($this->content ?? [], $this->style, $this->locale));
        $this->update([
            'path' => $path,
            'file_name' => CvTemplates::fileName($this->content['name'] ?? null, CvTemplates::labels($this->locale)['cv']),
            'status' => 'ready',
            'error' => null,
        ]);
        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }
    }

    // Deletes the resume and its file. When it was the main one, the newest ready resume takes its place.
    public function remove(): void
    {
        DB::transaction(function () {
            $this->path && Storage::disk('local')->delete($this->path);
            $this->delete();
            if ($this->is_main && ($next = static::where('candidate_id', $this->candidate_id)->where('status', 'ready')->latest('updated_at')->latest('id')->first())) {
                $next->update(['is_main' => true]);
            }
        });
    }

    // Languages it can be translated into with AI.
    public function translationTargets(): array
    {
        return $this->status === 'ready' ? array_values(array_diff(['en', 'fr'], [$this->locale])) : [];
    }

    // What lists and pages show.
    public function summary(): array
    {
        $language = fn (?string $code) => $code ? ['code' => $code, 'label' => LanguageGuess::name($code) ?? strtoupper($code)] : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'language' => $language($this->locale),
            'source' => $this->source,
            'status' => $this->status,
            'is_main' => $this->is_main,
            'editable' => $this->isEditable(),
            'has_file' => (bool) $this->path,
            'file_name' => $this->file_name,
            'style' => $this->isEditable() ? CvTemplates::style($this->style) : null,
            'error' => $this->error,
            'targets' => array_map($language, $this->translationTargets()),
            'translated_from' => $this->translated_from_id ? $this->translatedFrom?->only('id', 'name') : null,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'updated_label' => $this->updated_at?->diffForHumans(),
        ];
    }
}
