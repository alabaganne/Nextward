<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
		$auth_id = auth()->user()->id;
		$contacts = User::whereHas('sentMessages', function ($query) use ($auth_id) {
			$query->where('to_id', $auth_id);
		})
		->orWhereHas('receivedMessages', function ($query) use ($auth_id) {
			$query->where('from_id', $auth_id);
		})
		->orderBy('name')
		->get()
		->transform(function ($contact) use ($auth_id) {
			$latest_message = new MessageResource(
				Message::where(function ($query) use ($contact, $auth_id) {
					$query->where('from_id', $contact->id)->where('to_id', $auth_id);
				})
				->orWhere(function ($query) use ($contact, $auth_id) {
					$query->where('from_id', $auth_id)->where('to_id', $contact->id);
				})->latest()
				->first()
			);

			return [
				'id' => $contact->id,
				'name' => $contact->name,
				'email' => $contact->email,
				'image' => $contact->image,
				'userable_type' => $contact->userable_type,
				'latest_message' => $latest_message
			];
		});

		$selectedContact = $request->user_id ? User::find($request->user_id) : (sizeof($contacts) > 0 ? $contacts[0] : null);

		if($selectedContact) {
			$messages = MessageResource::collection(
				Message::where(function($query) use ($selectedContact) {
					$query->where('from_id', auth()->user()->id)
						->where('to_id', $selectedContact['id']);
				})->orWhere(function ($query) use ($selectedContact) {
					$query->where('from_id', $selectedContact['id'])
						->where('to_id', auth()->user()->id);
				})
				->orderBy('created_at', 'ASC')
				->get()
			);
		}

        return Inertia::render('Messages/Index', [
			'contacts' => $contacts,
			'selectedContact' => $selectedContact ? [
				'id' => $selectedContact['id'],
				'name' => $selectedContact['name'],
				'email' => $selectedContact['email'],
				'image' => $selectedContact['image'],
				'messages' => $messages ? $messages : null,
			] : null
		]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		$request->validate([
			'to_id' => ['required', 'integer', 'exists:users,id'],
			'text' => ['required', 'string', 'max:5000'],
		]);

		$message = Message::create([
			'from_id' => auth()->user()->id,
			'to_id' => $request->to_id,
			'text' => $request->text,
		]);

		try {
			broadcast(new NewMessage($request->to_id, $message));
		} catch (\Throwable $exception) {
			Log::warning('Realtime message broadcast failed; message was still saved.', [
				'message_id' => $message->id,
				'to_id' => $request->to_id,
				'error' => $exception->getMessage(),
			]);
		}

        return Redirect::route('messages.index', [ 'user_id' => $request->to_id ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return Redirect::route('messages.index', ['user_id' => $user->id]);
    }
}
