<?php

namespace App\Support;

use Illuminate\Support\Str;

class MailText
{
    // Text people typed goes into Markdown emails, so links and formatting are shown as plain text.
    public static function plain(?string $text, int $limit = 600): string
    {
        $text = trim(preg_replace("/\r\n?/", "\n", (string) $text));
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = Str::limit($text, $limit);
        $text = e(preg_replace('/([\\\\`*_{}\[\]()#+\-!|~])/', '\\\\$1', $text), false);

        return str_replace("\n", "  \n", $text);
    }

    public static function firstName(?string $name): string
    {
        return Str::of((string) $name)->trim()->before(' ')->value() ?: 'there';
    }
}
