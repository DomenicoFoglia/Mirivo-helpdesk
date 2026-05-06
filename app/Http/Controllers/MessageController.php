<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Mostra i ticket aperti all'utente
     */
    public function index(Ticket $ticket)
    {
        $user = Auth::user();

        $hasAccess = $ticket->user_id === $user->id || $ticket->assignee_id === $user->id;

        if(!$hasAccess){
            return response()->json([
                'message' => 'Azione non permessa'
            ], 403);
        }

        $messages = $ticket->messages()->latest()->paginate(15);

        return response()->json($messages);
    }

    /**
     * Salva un nuovo messaggio
     */
    public function store(Request $request, Ticket $ticket)
    {
        $user  = Auth::user();

        $hasAccess = $ticket->user_id === $user->id || $ticket->assignee_id === $user->id;

        if(!$hasAccess){
            return response()->json([
                'message' => 'Azione non permessa'
            ], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string'
        ]);
        
        return response()->json(Message::create([
            'body' => $validated['body'],
            'user_id' => $user->id,
            'ticket_id' => $ticket->id
        ]), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
