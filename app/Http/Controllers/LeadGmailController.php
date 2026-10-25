<?php

namespace App\Http\Controllers;

use App\Leads\GmailImport;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

// Connects the owner's Gmail with read-only access, so application emails become job leads.
class LeadGmailController extends Controller
{
    public function connect(Request $request)
    {
        abort_unless(GmailImport::configured() && Lead::enabledFor($request->user()), 404);
        $state = Str::random(40);
        $request->session()->put('gmail_state', $state);
        return redirect()->away(GmailImport::authUrl($state));
    }

    public function callback(Request $request)
    {
        $state = $request->session()->pull('gmail_state');
        if (! $state || ! hash_equals($state, (string) $request->query('state')) || ! is_string($request->query('code'))) {
            return $this->back('error', 'Gmail was not connected.');
        }
        try {
            GmailImport::connect($request->user(), $request->query('code'));
        } catch (Throwable $e) {
            Log::warning('Gmail connect failed.', ['user_id' => $request->user()->id, 'error' => Str::limit($e->getMessage(), 300)]);
            return $this->back('error', 'Gmail was not connected. '.Str::limit($e->getMessage(), 160));
        }
        return $this->back('store', 'Gmail connected. Application emails are read every morning before the new leads.');
    }

    public function sync(Request $request, GmailImport $gmail)
    {
        abort_unless($request->user()->gmail_token, 404);
        try {
            $stats = $gmail->sync($request->user(), 14);
        } catch (Throwable $e) {
            Log::warning('Gmail sync failed.', ['user_id' => $request->user()->id, 'error' => Str::limit($e->getMessage(), 300)]);
            return $this->back('error', 'Gmail could not be read. Try again later or connect it again.');
        }
        return $this->back('update', "{$stats['emails']} new emails read: {$stats['added']} added, {$stats['updated']} merged with existing leads.");
    }

    public function disconnect(Request $request)
    {
        GmailImport::disconnect($request->user());
        return $this->back('destroy', 'Gmail disconnected.');
    }

    private function back(string $type, string $message)
    {
        return redirect()->route('leads.index')->with('toast', ['type' => $type, 'message' => $message]);
    }
}
