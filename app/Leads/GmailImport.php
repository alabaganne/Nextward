<?php

namespace App\Leads;

use App\Ai\Agents\ApplicationEmailReader;
use App\Ai\OpportunityClassifier;
use App\Importing\JobKey;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

// Reads application emails from the owner's Gmail (read-only) and saves them as job leads.
class GmailImport
{
    public const SCOPE = 'https://www.googleapis.com/auth/gmail.readonly';
    private const API = 'https://gmail.googleapis.com/gmail/v1/users/me';
    private const SUBJECTS = ['thank you for applying', 'thanks for applying', 'thank you for your application', 'thanks for your application', 'application received',
        'we received your application', 'we have received your application', 'your application', 'application confirmation', 'candidature'];
    private const SENDERS = ['greenhouse.io', 'greenhouse-mail.io', 'lever.co', 'ashbyhq.com', 'workablemail.com', 'workable.com', 'myworkday.com', 'recruitee.com',
        'smartrecruiters.com', 'teamtailor.com', 'jobs-noreply@linkedin.com'];
    // Senders that are a job board or an email service, not the company.
    private const SERVICES = 'greenhouse|lever|ashby|workable|workday|recruitee|smartrecruiters|teamtailor|linkedin|indeed|gmail|google|outlook';

    public array $stats = ['emails' => 0, 'added' => 0, 'updated' => 0, 'left' => 0];

    public function __construct(private OpportunityClassifier $classifier) {}

    public static function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public static function redirect(): string
    {
        return config('services.google.redirect') ?: route('leads.gmail.callback');
    }

    public static function authUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'), 'redirect_uri' => self::redirect(), 'response_type' => 'code',
            'scope' => self::SCOPE, 'access_type' => 'offline', 'prompt' => 'consent', 'include_granted_scopes' => 'false', 'state' => $state,
        ]);
    }

    // Swaps the code from Google for a refresh token and saves it on the user.
    public static function connect(User $user, string $code): void
    {
        $token = self::token(['code' => $code, 'grant_type' => 'authorization_code', 'redirect_uri' => self::redirect()]);
        if (empty($token['refresh_token'])) { throw new RuntimeException('Google did not send a refresh token.'); }
        if (! str_contains((string) ($token['scope'] ?? self::SCOPE), self::SCOPE)) { throw new RuntimeException('Gmail read access was not granted.'); }
        $email = Http::withToken($token['access_token'])->timeout(20)->get(self::API.'/profile')->throw()->json('emailAddress');
        $user->forceFill(['gmail_token' => $token['refresh_token'], 'gmail_email' => $email, 'gmail_synced_at' => null])->save();
    }

    public static function disconnect(User $user): void
    {
        if ($user->gmail_token) { rescue(fn () => Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $user->gmail_token]), report: false); }
        $user->forceFill(['gmail_token' => null, 'gmail_email' => null, 'gmail_synced_at' => null])->save();
    }

    // Saves the application emails of the last $days days. Each email is read once, so a sync can run any number of times.
    public function sync(User $user, int $days = 3): array
    {
        $this->stats = ['emails' => 0, 'added' => 0, 'updated' => 0, 'left' => 0];
        $candidate = $user->ensureCandidateProfile();
        if (! $user->gmail_token || ! $candidate) { return $this->stats; }
        $access = self::token(['refresh_token' => $user->gmail_token, 'grant_type' => 'refresh_token'])['access_token'];
        $gmail = Http::withToken($access)->timeout(20)->retry(2, 500, throw: false);
        $known = Lead::where('candidate_id', $candidate->id)->where('origin', 'gmail')->pluck('external_ref')->flip();
        $emails = [];
        $page = null;
        do {
            $list = $gmail->get(self::API.'/messages', array_filter(['q' => $this->query($days), 'maxResults' => 100, 'pageToken' => $page]))->throw()->json();
            foreach ($list['messages'] ?? [] as $message) {
                if (isset($known[$message['id']]) || count($emails) >= 300) { continue; }
                $data = $gmail->get(self::API.'/messages/'.$message['id'].'?format=metadata&metadataHeaders=Subject&metadataHeaders=From&metadataHeaders=Date')->throw()->json();
                $headers = collect($data['payload']['headers'] ?? [])->mapWithKeys(fn ($header) => [mb_strtolower($header['name']) => $header['value']]);
                $emails[] = ['id' => $message['id'], 'subject' => trim((string) $headers->get('subject')), 'from' => trim((string) $headers->get('from')),
                    'snippet' => html_entity_decode((string) ($data['snippet'] ?? ''), ENT_QUOTES | ENT_HTML5),
                    'date' => isset($data['internalDate']) ? Carbon::createFromTimestampMs($data['internalDate']) : rescue(fn () => Carbon::parse($headers->get('date')), null, false)];
            }
            $page = $list['nextPageToken'] ?? null;
        } while ($page && count($emails) < 300);
        $this->stats['emails'] = count($emails);

        $read = collect($emails)->mapWithKeys(fn ($email) => [$email['id'] => $this->read($email)]);
        $this->askAi(collect($emails)->filter(fn ($email) => ! $read[$email['id']]['company'] || ! $read[$email['id']]['role']))
            ->each(function ($answer, $id) use ($read) {
                $found = $read[$id];
                $read[$id] = ['kind' => $answer['kind'], 'company' => $found['company'] ?: $answer['company'], 'role' => $found['role'] ?: $answer['role']];
            });

        $import = new LeadImport($candidate);
        foreach ($emails as $email) {
            $found = $read[$email['id']];
            $found['role'] = $found['role'] ?: $this->onlyRoleAt($candidate->id, $found['company']);
            if ($found['kind'] === 'other' || ! $found['company'] || ! $found['role']) { $this->stats['left']++; continue; }
            $import->save(['id' => $email['id'], 'company' => $found['company'], 'title' => $found['role'], 'status' => $found['kind'],
                'applied_at' => $found['kind'] === 'rejected' ? null : $email['date']?->toDateString(), 'notes' => 'From Gmail: '.$email['subject']], 'gmail');
        }
        $this->stats['added'] = $import->stats['added'];
        $this->stats['updated'] = $import->stats['updated'];
        $user->forceFill(['gmail_synced_at' => now()])->save();
        return $this->stats;
    }

    // Company, role and kind read from the subject, sender and first lines; empty when the patterns do not say.
    public function read(array $email): array
    {
        $subject = preg_replace('/^(?:re|fwd?|tr)\s*:\s*/i', '', $email['subject']);
        $text = $subject.' '.$email['snippet'];
        $kind = match (true) {
            (bool) preg_match('/unfortunately|regret to inform|not (?:to )?mov(?:e|ing) forward|decided to (?:pursue|move forward with) other|not been selected|other candidates|will not be moving|no longer under consideration|malheureusement|pas retenu/i', $text) => 'rejected',
            (bool) preg_match('/\binterview\b|entretien|schedule (?:a|your) (?:call|chat)|coding (?:challenge|test|assessment)|take-?home/i', $subject) => 'interviewing',
            (bool) preg_match('/job alert|jobs? (?:for you|you may|matching)|new jobs|recommended jobs|apply now|is hiring|newsletter/i', $subject) => 'other',
            default => 'applied',
        };
        $role = $company = null;
        $patterns = [
            '/(?:applying|application|applied|interest)\s+(?:to|for|in)\s+(?:the\s+)?(?<role>.+?)\s+(?:position|role|job|opening|opportunity)?\s*(?:at|with|chez)\s+(?<company>[^!.,|]+)/i',
            '/(?:application|candidature)\s+(?:for|to|pour)\s+(?:the\s+|le poste (?:de\s+)?)?(?<role>.+?)\s*(?:position|role)?\s*[-|:@]\s*(?<company>[^!.,|]+)$/iu',
            '/your application (?:was sent|has been sent|was submitted) to (?<company>[^!.,|]+)/i',
            '/(?:thank you|thanks) for (?:applying|your (?:application|interest))(?: to| at| in| with)\s+(?<company>[^!.,|]+)/i',
            '/(?:application|candidature)\s+(?:to|at|chez)\s+(?<company>[^!.,|]+)/i',
            '/(?:application received|we received your application|we have received your application)(?: for| to)?\s*[-:]?\s*(?:the\s+)?(?<role>[^!.,|]+?)(?:\s+(?:position|role))?$/i',
            '/(?:^|[:\x{2013}-]\s*)(?<role>[^:\x{2013}-]{3,80}?)\s+(?:at|@|chez)\s+(?<company>[^!.,|:]+)$/u',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $subject, $match)) {
                $role = $this->clean($match['role'] ?? null);
                $company = $this->clean($match['company'] ?? null);
                if ($company && preg_match('/^(?:the|our|a|an)\b|\b(?:position|role|job)$/i', $company)) { $company = null; }
                break;
            }
        }
        $role ??= preg_match('/(?:for|of|in) the (?:position of |role of )?(?<role>[A-Z][^.,!]{2,80}?) (?:position|role|job)\b/u', $subject.'. '.$email['snippet'], $match) ? $this->clean($match['role']) : null;
        $company ??= $this->sender($email['from']);
        return ['kind' => $kind, 'company' => $company, 'role' => $role];
    }

    private function query(int $days): string
    {
        $subjects = implode(' OR ', array_map(fn ($subject) => '"'.$subject.'"', self::SUBJECTS));
        return 'newer_than:'.max(1, $days).'d -in:chats -in:sent (subject:('.$subjects.') OR from:('.implode(' OR ', self::SENDERS).'))';
    }

    // The company from a sender like "Acme Hiring Team <no-reply@greenhouse.io>" or "jobs@acme.com".
    private function sender(string $from): ?string
    {
        preg_match('/^\s*"?(?<name>[^"<]*?)"?\s*<?(?<email>[^<>\s]+@[^<>\s]+?)>?\s*$/', $from, $match);
        $name = trim(preg_replace('/^the\s+|\b(?:hiring|recruiting|recruitment|talent(?: acquisition)?|careers?|jobs?|team|people|hr|no-?reply|notifications?|via \w+)\b/i', '', $match['name'] ?? ''), " \t-|@,.");
        if ($name !== '' && ! preg_match('/^(?:'.self::SERVICES.')\b/i', $name)) { return $this->clean($name); }
        $domain = Str::after(mb_strtolower($match['email'] ?? ''), '@');
        $label = Str::before(preg_replace('/^(?:mail|email|e|jobs|careers|talent|hr)\./', '', $domain), '.');
        return $label !== '' && ! preg_match('/'.self::SERVICES.'/i', $domain) ? Str::title($label) : null;
    }

    // A rejection that names only the company belongs to the one open application there, when there is only one.
    private function onlyRoleAt(int $candidateId, ?string $company): ?string
    {
        if (! $company) { return null; }
        $leads = Lead::where('candidate_id', $candidateId)->where('company_key', JobKey::company($company))->whereIn('status', ['opened', 'applied', 'interviewing'])->limit(2)->pluck('title');
        return $leads->count() === 1 ? $leads->first() : null;
    }

    // Emails the patterns could not read, sent to the AI in small batches: subject, sender and first lines only.
    private function askAi($emails)
    {
        $answers = collect();
        if (! $this->classifier->enabled() || $emails->isEmpty()) { return $answers; }
        foreach ($emails->chunk(15) as $chunk) {
            $prompt = $chunk->map(fn ($email) => "Email {$email['id']}\nSubject: {$email['subject']}\nFrom: {$email['from']}\nFirst lines: ".Str::limit($email['snippet'], 300))->implode("\n\n");
            try {
                $reply = (new ApplicationEmailReader)->prompt($prompt, provider: $this->classifier->provider(), model: $this->classifier->model('review'))->toArray();
            } catch (Throwable $e) {
                Log::warning('Gmail email reading failed.', ['error' => Str::limit($e->getMessage(), 300)]);
                continue;
            }
            foreach ($reply['emails'] ?? [] as $answer) {
                if ($chunk->contains('id', $answer['id'] ?? null)) {
                    $answers[$answer['id']] = ['kind' => $answer['kind'], 'company' => $this->clean($answer['company']), 'role' => $this->clean($answer['role'])];
                }
            }
        }
        return $answers;
    }

    private function clean(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value), " \t\"'!.,:-|");
        return $value === '' || mb_strlen($value) > 120 ? null : $value;
    }

    private static function token(array $data): array
    {
        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'), 'client_secret' => config('services.google.client_secret'), ...$data,
        ]);
        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException('Google said no: '.Str::limit((string) ($response->json('error_description') ?? $response->json('error') ?? $response->status()), 160));
        }
        return $response->json();
    }
}
