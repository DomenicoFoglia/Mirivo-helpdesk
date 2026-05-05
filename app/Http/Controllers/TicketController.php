<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $tickets = $user->userTickets()->latest()->paginate(15);

        return response()->json($tickets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $newTicket = DB::transaction(function() use ($request){
            $user = Auth::user();

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'message' => 'required|string'
            ]);

            $ticket = Ticket::create([
                'title' => $validated['title'],
                'category_id' => $validated['category_id'],
                'user_id' => $user->id,
                'company_id' => $user->company->id
            ]);

            Message::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'body' => $validated['message']
            ]);

            return $ticket;
        });

        return response()->json([
            'message' => 'Ticket creato con successo',
            'ticket' => $newTicket
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        // Non inserirsco WITH('MESSAGES') perche' se il ticket ha 500 messaggi questi verrebero
        // caricati tutti insieme appesantendo la richiesta. Li gestiremo in MessageController
        $ticket = $user->userTickets()->findOrFail($id);

        return response()->json($ticket);
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
