<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Bots fill the hidden "website" field; pretend it worked.
        if (filled($request->input('website'))) {
            return back()->with('status', 'contact-sent');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:200'],
            'topic' => ['required', Rule::in(array_keys(ContactMessage::TOPICS))],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'page_url' => ['nullable', 'url', 'max:300'],
        ]);

        try {
            Mail::to(config('mail.contact_address'))->send(new ContactMessage($data));
        } catch (\Throwable $exception) {
            Log::error('Contact form email failed.', ['error' => $exception->getMessage()]);

            return back()->withErrors(['message' => 'Your message could not be sent. Please try again in a few minutes.'])->withInput();
        }

        return back()->with('status', 'contact-sent');
    }
}
