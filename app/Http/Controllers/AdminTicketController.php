<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminTicketController extends Controller
{

    /**
     * Lista ticket
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $tickets = Ticket::where('company_id', $user->company_id)
            ->with([
                'assignee:id,name,surname,level',
                'category',
                'user:id,name,surname',
            ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('priority'), function ($query) use ($request) {
                $query->where('priority', $request->priority);
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
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
     * Mostra il ticket
     */
    public function show(string $id){
        $ticket = Ticket::with([
            'assignee:id,name,surname,level',
            'category',
            'user:id,name,surname'
            ])
            ->findOrFail($id);

        if(!Gate::allows('view', $ticket)){
            abort(404);
        }

        return $ticket;
    }


    /**
     * Aggiorna la priorita' del ticket
     */
    public function updatePriority(Request $request, string $id){
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'priority' => 'nullable|in:low,medium,high'
        ]);

        $ticket->priority = $validated['priority'];
        $ticket->save();

        return $ticket;
    }

    /**
     * Aggiorna lo status del ticket
     */
    public function updateStatus(Request $request, string $id){
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,working'
        ]);

        $ticket->status = $validated['status'];
        $ticket->save();

        return $ticket;
    }


    /**
     * Aggiorna la priorita' del ticket
     */
    public function escalate(Request $request, string $id){
        $user = Auth::user();

        $ticket = Ticket::where('company_id', $user->company_id)
            ->findOrFail($id);

        $ticket->assignee_id = null;
        $ticket->status = 'escalated';
        $ticket->save();

        return $ticket;

    }

    /**
     * Mostra i ticket 'escalated' disponibili
     */
    public function escalatedAvailable() {
        $user = Auth::user();

        $tickets = Ticket::where('company_id', $user->company_id)
            ->where('status', 'escalated')
            ->whereNull('assignee_id')
            ->with(['user', 'category'])
            ->orderByRaw("FIELD(IFNULL(priority, 'low'), 'high', 'medium', 'low')")
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return $tickets;
    }




}
