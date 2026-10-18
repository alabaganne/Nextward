<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// A verified address must confirm the move first; an unverified one can be corrected right away.
class EmailChangeController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $request->merge(['email' => trim((string) $request->input('email'))]);
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id), function ($attribute, $value, $fail) use ($user) {
                if (mb_strtolower($value) === mb_strtolower($user->email)) { $fail('This is already your email.'); }
            }],
            'password' => ['required', 'current_password'],
        ], ['email.unique' => 'Another account already uses this email.']);

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email' => $data['email'], 'pending_email' => null])->save();
            $this->sendVerification($user);

            return back()->with('toast', ['type' => 'update', 'message' => "Email changed. We sent a link to {$user->email} to verify it."]);
        }

        $user->forceFill(['pending_email' => $data['email']])->save();
        try {
            $user->notify(new ConfirmEmailChange);
        } catch (\Throwable $exception) {
            Log::warning('Email change link failed to send.', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
            throw ValidationException::withMessages(['email' => 'We could not send the email right now. Please try again in a minute.']);
        }

        return back()->with('toast', ['type' => 'update', 'message' => "Check {$user->email}: we sent a link to confirm the change."]);
    }

    public function destroy(Request $request)
    {
        $request->user()->forceFill(['pending_email' => null])->save();

        return back()->with('toast', ['type' => 'update', 'message' => 'Email change cancelled.']);
    }

    public function confirm(Request $request, User $user, string $hash)
    {
        $signedIn = Auth::id() === $user->id;
        $done = fn (string $message) => $signedIn
            ? redirect()->route('settings')->with('toast', ['type' => 'update', 'message' => $message])
            : redirect()->route('login')->with('status', $message);

        if (! $user->pending_email || ! hash_equals(sha1($user->pending_email), $hash)) {
            return $done('This link is no longer valid. Ask for a new one in Settings.');
        }
        if (User::where('email', $user->pending_email)->whereKeyNot($user->id)->exists()) {
            $user->forceFill(['pending_email' => null])->save();
            return $done('Another account now uses that email, so we did not change yours.');
        }

        $user->forceFill(['email' => $user->pending_email, 'pending_email' => null, 'email_verified_at' => null])->save();
        $this->sendVerification($user);

        return $done("Your email is now {$user->email}. We sent a link there to verify it.");
    }

    private function sendVerification(User $user): void
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            Log::warning('Verification email failed after an email change.', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
        }
    }
}
