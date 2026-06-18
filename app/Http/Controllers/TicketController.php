<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $tickets = $user->userTickets()
            ->with(['category', 'assignee:id,name,surname,level'])
            ->when($request->filled('state'), function ($q) use ($request) {
                if ($request->state === 'closed') {
                    $q->where('status', 'closed');
                } elseif ($request->state === 'open') {
                    $q->whereIn('status', ['open', 'working', 'escalated']);
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return $tickets;
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
                'category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $user->company_id)],
                'message' => 'required|string'
            ]);

            $ticket = Ticket::create([
                'title' => $validated['title'],
                'category_id' => $validated['category_id'],
                'user_id' => $user->id,
                'company_id' => $user->company_id
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
        $ticket = Ticket::with([
            'assignee:id,name,surname,level',
            'category',
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
