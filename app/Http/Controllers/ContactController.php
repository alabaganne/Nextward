<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'page_url' => ['nullable', 'url', 'max:300'],
            'screenshot' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $data['account'] = $user ? '#'.$user->id.' · '.($user->isAdmin() ? 'admin' : $user->userable_type).' · '.$user->email : null;
        $data['browser'] = $data['topic'] === 'bug' ? Str::limit((string) $request->userAgent(), 300) : null;

        $screenshot = null;
        if ($file = $request->file('screenshot')) {
            $screenshot = ['data' => $file->get(), 'name' => 'screenshot.'.$file->extension(), 'mime' => $file->getMimeType()];
        }
        unset($data['screenshot']);

        try {
            Mail::to(config('mail.contact_address'))->send(new ContactMessage($data, $screenshot));
        } catch (\Throwable $exception) {
            Log::error('Contact form email failed.', ['error' => $exception->getMessage()]);

            return back()->withErrors(['message' => 'Your message could not be sent. Please try again in a few minutes.'])->withInput();
        }

        return back()->with('status', 'contact-sent');
    }
}
