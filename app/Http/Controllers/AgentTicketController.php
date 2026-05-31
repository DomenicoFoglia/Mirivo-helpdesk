<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AgentTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $tickets = $user->assigneeTickets()
            ->where('company_id', $user->company_id)
            ->with([
                'category',
                'user:id,name,surname'
            ])
            ->when($request->filled('status'), function ($query) use ($request){
                $query->where('status', $request->status);
            })
            ->when($request->filled('priority'), function($query) use ($request){
                $query->where('priority', $request->priority);
            })
            ->when($request->filled('category_id'), function($query) use ($request){
                $query->where('category_id', $request->category_id);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return $tickets;
    }

    /**
     * Restituisce i ticket non ancora assegnati
     */
    public function available(Request $request){

        $user = Auth::user();

        $tickets = Ticket::where('company_id', $user->company_id)
            ->where('status', 'open')
            ->whereNull('assignee_id')
            ->with(['user:id,name,surname', 'category'])
            ->when($request->filled('priority'), function ($query) use ($request){
                $query->where('priority', $request->priority);
            })
            ->when($request->filled('category_id'), function ($query) use ($request){
                $query->where('category_id', $request->category_id);
            })
            ->when($request->filled('search'), function ($query) use ($request){
                $query->where('title', 'like', '%' . $request->search . '%');
            })
            ->orderByRaw("FIELD(IFNULL(priority, 'low'), 'high', 'medium', 'low')")
            ->orderBy('created_at','asc')
            ->paginate(15)
            ->withQueryString();

        return $tickets;
    }

    /**
     * Restituisce i ticket 'scalati' non ancora assegnati
     */
    public function escalatedAvailable(){
        $user = Auth::user();

        $tickets= Ticket::where('company_id', $user->company_id)
            ->where('status', 'escalated')
            ->whereNull('assignee_id')
            ->with(['user', 'category'])
            ->orderByRaw("FIELD(IFNULL(priority, 'low'), 'high', 'medium', 'low')")
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return $tickets;
    }


    /**
     * Permeette a un tecnico di assegnarsi un ticket
     */
    public function assign(string $id){
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->where('status', 'open')
            ->findOrFail($id);

        // Avreei potuto aggiungere ->whereNull('assignee_id') direttamente nella query precedente ma
        // nell'eventualita' di 2 tecnici che cercano di prendere lo stesso ticket quasi contemporanemante
        // almeno avremo un messaggio chiaro nel frontend di quello che stga succedendo.
        // Esiste anche un metodo che blocca la riga nel DB durante la transazione ma a noi non serve
        if($ticket->assignee_id !== null){
            return response()->json(['message' => 'Ticket gia preso in consegna'], 409);
        }

        $ticket->assignee_id = $user->id;
        $ticket->status = 'working';

        $ticket->save();

        return $ticket;
    }

    /**
     * Dato un ticket scalato, lo assegna ad un tecnico L2
     */
    public function assignEscalated(string $id){
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->where('status', 'escalated')
            ->findOrFail($id);

        if($ticket->assignee_id !== null){
            return response()->json(['message' => 'Ticket gia preso in consegna'], 409);
        }

        $ticket->assignee_id = $user->id;
        $ticket->status = 'working';

        $ticket->save();

        return $ticket;
    }

    /**
     * Chiude un ticket
     */
    public function close(string $id){
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->where('assignee_id', $user->id)
            ->findOrFail($id);

        $ticket->status = 'closed';
        $ticket->closed_at = now();
        $ticket->save();

        return $ticket;
    }

    /**
     * Aggiorna stato del ticket
     */
    public function updateStatus(Request $request, string $id){
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->where('assignee_id', $user->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,working'
        ]);

        $ticket->status = $validated['status'];
        $ticket->save();

        return $ticket;
    }

    /**
     * Aggiorna priorita' del  ticket
     */
    public function updatePriority(Request $request, string $id){
        $user = Auth::user();

        $ticket= Ticket::where('company_id', $user->company_id)
            ->where('assignee_id', $user->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'priority' => 'nullable|in:low,medium,high'
        ]);

        $ticket->priority = $validated['priority'];
        $ticket->save();

        return $ticket;
    }


    /**
     * Scala il ticket ad un Tecnico si secondo livello, JsonResponse e' solo per assicurarci che ritorniamo un json
     */
    public function escalate(Request $request, string $id): JsonResponse{
        $user = Auth::user();

        // Solo L1 puo' scalare
        if($user->level !== 1){
            return response()->json([
                'message' => 'Solo i tecnici L1 possono scalare un ticket!'
            ], 403);
        }

        $ticket = Ticket::where('company_id', $user->company_id)
                        ->where('assignee_id', $user->id)
                        ->findOrFail($id);

        $assigneeId = null;

        if( $request->filled('assignee_id')){
            $l2 = User::where('id', $request->assignee_id)
                        ->where('company_id', $user->company_id)
                        ->where('role', 'agent')
                        ->where('level', 2)
                        ->firstOrFail();
            
            $assigneeId = $l2->id;
        }

        $ticket->assignee_id = $assigneeId;
        $ticket->status = 'escalated';
        $ticket->save();

        return response()->json(['message' => 'Ticket scalato con successo', 'ticket' => $ticket]);
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
        $ticket = Ticket::with([
            'assignee:id,name,surname,level',
            'category',
            'user:id,name,surname',
        ])->findOrFail($id);

        if(!Gate::allows('view', $ticket)){
            abort(404);
        }

        return $ticket;
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
