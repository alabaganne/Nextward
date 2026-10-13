<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $attributes = [
        'channel' => 'internal',
        'status' => 'submitted',
    ];

    protected $fillable = [
        'cover_letter',
        'message',
        'attachments',
        'status',
        'student_id',
        'internship_id',
        'company_id',
        'channel',
        'applied_at',
        'candidate_notes',
    ];

    protected $casts = [
        'attachments' => 'array',
        'applied_at' => 'date:Y-m-d',
    ];

    public function attachmentLinks(): array
    {
        return collect($this->attachments ?: [])->map(fn ($attachment, $index) => [
            'name' => $attachment['name'] ?? 'Attachment',
            'size' => $attachment['size'] ?? null,
            'mime' => $attachment['mime'] ?? null,
            'url' => route('applications.attachments.download', [$this, $index]),
        ])->values()->all();
    }

    public static function attachmentStorage(array $attachment): ?array
    {
        if (($attachment['disk'] ?? null) === 'local' && isset($attachment['path'])) {
            $path = $attachment['path'];
            $disk = 'local';
        } else {
            $urlPath = parse_url($attachment['url'] ?? '', PHP_URL_PATH);
            if (!is_string($urlPath) || !str_starts_with($urlPath, '/storage/applications/')) {
                return null;
            }
            $path = substr($urlPath, strlen('/storage/'));
            $disk = 'public';
        }

        if (!str_starts_with($path, 'applications/') || str_contains($path, '..') || str_contains($path, '\\') || str_contains($path, "\0")) {
            return null;
        }

        return compact('disk', 'path');
    }

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function internship() {
        return $this->belongsTo(Internship::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }
}
