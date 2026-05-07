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
        if($user->company_id !== $ticket->company_id){
            return response()->json([
                'message' => 'Non autorizzato'
            ], 403);
        }
        
        $query= $ticket->messages()->latest();

        if($user->role === 'user'){
            if($user->id !== $ticket->user_id){
                return response()->json([
                    'message' => 'Non autorizzato'
                ], 403);
            }else{
                $query = $query->where('type', 'public');
            } 
        }

        $messages = $query->paginate(15);

        return response()->json($messages);
    }

    /**
      * Salva un nuovo messaggio
     */
    public function store(Request $request, Ticket $ticket)
    {
        $user  = Auth::user();

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

        if(!$hasAccess || $user->company_id !== $ticket->company_id){
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
