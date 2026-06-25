<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Mostra i ticket aperti all'utente
     */
    public function index(string $id)
    {
        $user = Auth::user();
        
        // Trova il ticket (404 se non esiste)
        $ticket = Ticket::findOrFail($id);

        // Se la Policy nega ci comportiamoci come se il ticket non esistesse (404, non 403)
        if (!Gate::allows('view', $ticket)) {
            abort(404);
        }

        // Lettura messaggi con filtro ruolo
        $messagesQuery = $ticket->messages()->with(['user:id,name,surname,role', 'attachments']);

        if ($user->role === 'user') {
            $messagesQuery->where('type', 'public');
        }

        return $messagesQuery->paginate(15);
    }

    /**
      * Salva un nuovo messaggio
     */
    public function store(Request $request, string $id)
    {
        $user = Auth::user();

        // Recupera ticket 
        $ticket = Ticket::where('company_id', $user->company_id)
            ->findOrFail($id);

        // Valida
        $validated = $request->validate([
            'body' => 'required_without:attachments|nullable|string',
            'type' => 'nullable|in:public,private',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => [
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,text/plain'
            ]
        ]);

        // Check permessi
        $type = $validated['type'] ?? 'public';

        if ($type === 'public') {
            $hasAccess = $ticket->user_id === $user->id || $ticket->assignee_id === $user->id || $user->role === 'admin';
        } else {
            $hasAccess = $user->role === 'agent' || $user->role === 'admin';
        }

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Azione non permessa'
            ], 403);
        }

        // Array per i file da salvare DOPO la transazione
        $pendingFiles = [];

        // Transazione: SOLO scritture su DB
        $message = DB::transaction(function () use ($request, $ticket, $user, $validated, $type, &$pendingFiles) {
            $msg = Message::create([
                'body' => $validated['body'] ?? '',
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'type' => $type
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = "attachments/{$msg->id}/{$filename}";

                    Attachment::create([
                        'message_id' => $msg->id,
                        'user_id' => $user->id,
                        'filename' => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    $pendingFiles[] = [
                        'file' => $file,
                        'directory' => "attachments/{$msg->id}",
                        'filename' => $filename,
                    ];
                }
            }

            return $msg;
        });

        // Transazione COMMIT-ata. Salvataggio file su disk.
        foreach ($pendingFiles as $pf) {
            $pf['file']->storeAs($pf['directory'], $pf['filename'], 'local');
        }

        $message->load(['attachments', 'user:id,name,surname,role']);

        return response()->json($message, 201);
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
