<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentMessageController extends Controller
{
    /** Polled every 5 seconds by the chat: messages newer than ?after=ID. */
    public function index(Request $request, Incident $incident)
    {
        Gate::authorize('view', $incident);
        $viewer = $request->user();
        $incident->markReadFor($viewer);

        $messages = $incident->messages()->with('user')
            ->where('id', '>', $request->integer('after'))
            ->get()
            ->map(fn ($message) => $message->toChatArray($viewer));

        return response()->json(['messages' => $messages, 'closed' => $incident->isClosed()]);
    }

    /** Works with fetch (JSON) and with a normal form post when JavaScript is off. */
    public function store(Request $request, Incident $incident)
    {
        Gate::authorize('view', $incident);

        if ($incident->isClosed()) {
            $error = 'Cet incident est clôturé : la conversation est en lecture seule.';

            return $request->expectsJson()
                ? response()->json(['message' => $error, 'closed' => true], 422)
                : back()->withErrors(['body' => $error])->withFragment('conversation');
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], [
            'body.required' => 'Écrivez un message avant de l’envoyer.',
            'body.max' => 'Votre message ne peut pas dépasser 2 000 caractères.',
        ]);
        $message = $incident->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message->load('user')->toChatArray($request->user())], 201);
        }

        return back()->withFragment('conversation');
    }
}
