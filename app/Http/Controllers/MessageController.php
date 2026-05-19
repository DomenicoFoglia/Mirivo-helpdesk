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
    public function index(string $id)
    {
        $user = Auth::user();
        
        $query = Ticket::where('company_id', $user->company_id);

        if($user->role === 'user'){
            $query->where('user_id', $user->id);
        }

        $ticket = $query->findOrFail($id);

        $messagesQuery = $ticket->messages()->latest();

        if($user->role === 'user'){
            $messagesQuery->where('type', 'public');
        }

        return $messagesQuery->paginate(15);
    }

    /**
      * Salva un nuovo messaggio
     */
    public function store(Request $request, string $id)
    {
        $user  = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'body' => 'required|string',
            'type' => 'nullable|in:public,private'
        ]);

        $type = $validated['type'] ?? 'public';

        if($type === 'public'){
            $hasAccess = $ticket->user_id === $user->id || $ticket->assignee_id === $user->id || $user->role === 'admin';
        }else{
            $hasAccess = $ticket->assignee_id === $user->id || $user->role === 'admin';
        }

        if(!$hasAccess){
            return response()->json([
                'message' => 'Azione non permessa'
            ], 403);
        }
        
        return response()->json(Message::create([
            'body' => $validated['body'],
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'type' => $type
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
