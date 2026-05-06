<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        //Rimosso, abbiamo gia gestito questo controllo nel middleware
        // if($user->role !== 'agent'){
        //     return response()->json([
        //         'message' => 'Non autorizzato'
        //     ], 404);
        // }

        $tickets = $user->assigneeTickets()->paginate(15);

        return response()->json($tickets);
    }

    /**
     * Restituisce i ticket non ancora assegnati
     */
    public function available(){

        $user = Auth::user();

        $tickets = Ticket:: where('company_id', $user->company_id)
                            ->where('status', 'open')
                            ->whereNull('assignee_id')
                            ->paginate(15);

        return response()->json([
            'tickets' => $tickets
        ], 200);
    }


    /**
     * permeette a un tecnico di assegnarsi un ticket
     */
    public function assign(Ticket $ticket){
        $user = Auth::user();

        if($ticket->assignee_id !== null){
            return response()->json([
                'message' => 'Ticket gia preso in consegna'
            ], 403);
        }

        $ticket->assignee_id = $user->id;

        $ticket->save();

        return response()->json($ticket, 200);
    }

    /**
     * Chiude un ticket
     */
    public function close(Ticket $ticket){
        $user = Auth::user();

        if($user->id !== $ticket->assignee_id || $user->company_id !== $ticket->company_id){
            return response()->json([
                'message' => 'Non autorizzato'
            ], 403);
        }

        $ticket->status = 'closed';
        $ticket->closed_at = now();
        $ticket->save();

        return response()->json($ticket, 200);
    }

    /**
     * Aggiorna stato del ticket
     */
    public function updateStatus(Ticket $ticket, Request $request){
        $user = Auth::user();

        if($user->id !== $ticket->assignee_id || $user->company_id !== $ticket->company_id){
            return response()->json([
                'message' => 'Non autorizzato'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,working'
        ]);

        $ticket->status = $validated['status'];
        $ticket->save();

        return response()->json($ticket, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)->findOrFail($id);

        return response()->json($ticket, 200);
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
