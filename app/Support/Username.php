<?php

namespace App\Support;

use Illuminate\Validation\Rule;

// Usernames are public profile links (/candidates/{username}), so they stay short, lowercase and readable.
// They start with a letter, which keeps them apart from the old /candidates/{id}-{name} links.
class Username
{
    public const PATTERN = '[a-z][a-z0-9-]{1,28}[a-z0-9]';
    // A profile link segment: a username, or {id}-{name} before one is set.
    public const PROFILE_SLUG = '[0-9]+-[a-z0-9-]+|'.self::PATTERN;

    // Words other pages use, or that could pass for Nextward itself.
    public const RESERVED = ['admin', 'api', 'app', 'candidate', 'candidates', 'companies', 'company', 'create', 'dashboard', 'edit', 'help',
        'login', 'logout', 'manage', 'messages', 'new', 'nextward', 'opportunities', 'profile', 'register', 'resume', 'settings', 'support', 'talent'];

    public static function clean(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_strtolower(trim($value)) : null;
    }

    public static function rules(?int $userId = null): array
    {
        return ['nullable', 'string', 'min:3', 'max:30', 'regex:/^'.self::PATTERN.'$/', 'not_regex:/--/',
            Rule::notIn(self::RESERVED), Rule::unique('users', 'username')->ignore($userId)];
    }

    public static function messages(): array
    {
        return [
            'username.regex' => 'Use 3 to 30 lowercase letters, numbers or dashes, starting with a letter.',
            'username.not_regex' => 'Use one dash at a time.',
            'username.min' => 'Use at least 3 characters.',
            'username.not_in' => 'This username is reserved. Pick another one.',
            'username.unique' => 'Someone already uses this username.',
        ];
    }
}
