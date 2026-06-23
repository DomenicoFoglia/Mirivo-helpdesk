<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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
        $user = Auth::user();

        // Valida
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $user->company_id)],
            'message' => 'required|string',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,odt,txt'
        ]);

        // Array per i file da salvare DOPO la transazione
        $pendingFiles = [];

        // Transazione: SOLO scritture su DB
        $newTicket = DB::transaction(function () use ($request, $user, $validated, &$pendingFiles) {
            $ticket = Ticket::create([
                'title' => $validated['title'],
                'category_id' => $validated['category_id'],
                'user_id' => $user->id,
                'company_id' => $user->company_id
            ]);

            $message = Message::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'body' => $validated['message']
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = "attachments/{$message->id}/{$filename}";

                    Attachment::create([
                        'message_id' => $message->id,
                        'user_id' => $user->id,
                        'filename' => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    $pendingFiles[] = [
                        'file' => $file,
                        'directory' => "attachments/{$message->id}",
                        'filename' => $filename,
                    ];
                }
            }

            return $ticket;
        });

        // Transazione COMMIT-ata. Salvataggio file su disk.
        foreach ($pendingFiles as $pf) {
            $pf['file']->storeAs($pf['directory'], $pf['filename'], 'local');
        }

        $newTicket->load(['category', 'messages.attachments']);

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
