<?php

namespace App\Leads;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// Scores a role for the owner: the kind of role (frontend first, then full stack, applied AI and backend), the skills it
// asks for, whether it can be taken from Tunisia and how new it is. Returns [score, reasons, fit], where fit only counts the role and skills.
class Scorer
{
    public function score(string $title, string $text, string $eligibility, ?Carbon $posted): array
    {
        $title = mb_strtolower(Str::ascii($title));
        $text = mb_strtolower(Str::ascii($title.' '.strip_tags($text)));
        $score = 0;
        $reasons = [];
        $best = 0;
        foreach (config('leads.groups') as $group) {
            if (preg_match($group['title'], $title) && $group['weight'] > $best) {
                $best = $group['weight'];
                $reasons['title'] = $group['label'].' role';
            }
            $found = array_values(array_filter($group['skills'], fn ($skill) => preg_match('/(?<![a-z0-9])'.preg_quote(mb_strtolower($skill), '/').'(?![a-z0-9])/', $text)));
            if ($found) {
                $score += $group['weight'] * min(count($found), 3);
                $reasons[] = implode(', ', array_slice($found, 0, 4));
            }
        }
        $score += $best * 2;
        $fit = $score;
        $score += config('leads.eligibility_points.'.$eligibility, 0);
        $reasons[] = ['remote' => 'Remote from Tunisia', 'visa' => 'Visa or relocation offered', 'unclear' => 'Check where you can work from'][$eligibility] ?? null;
        if ($posted) {
            $days = $posted->diffInDays(now());
            $score += $days < 1 ? 2 : ($days < 3 ? 1 : 0);
            if ($days < 1) { $reasons[] = 'Posted today'; }
        }
        return [$score, array_values(array_filter($reasons)), $fit];
    }
}
