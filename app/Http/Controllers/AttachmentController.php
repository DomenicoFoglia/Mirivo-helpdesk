<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(string $id)
    {
        $user = Auth::user();

        // Recupera attachment con relazioni
        $attachment = Attachment::with('message.ticket')->findOrFail($id);

        // Check policy sul ticket: 404-on-deny
        if (!Gate::allows('view', $attachment->message->ticket)) {
            abort(404);
        }

        // Messaggio privato: nascondilo agli utenti
        if ($attachment->message->type === 'private' && $user->role === 'user') {
            abort(404);
        }

        // Restituisci il file forzando il download
        return Storage::disk('local')->download($attachment->path, $attachment->original_filename);
    }

    public function preview(string $id)
    {
        $user = Auth::user();

        $attachment = Attachment::with('message.ticket')->findOrFail($id);

        if (!Gate::allows('view', $attachment->message->ticket)) {
            abort(404);
        }

        if ($attachment->message->type === 'private' && $user->role === 'user') {
            abort(404);
        }

        // Solo immagini
        $imageMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($attachment->mime_type, $imageMimes)) {
            abort(415, 'Anteprima disponibile solo per immagini');
        }

        return Storage::disk('local')->response($attachment->path, $attachment->original_filename, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();

        $attachment = Attachment::with('message.ticket')->findOrFail($id);

        // Multi-tenant: l'attachment deve appartenere a un ticket della company dell'utente
        if ($attachment->message->ticket->company_id !== $user->company_id) {
            abort(404);
        }

        // Permessi: autore o admin
        $canDelete = $attachment->user_id === $user->id || $user->role === 'admin';

        if (!$canDelete) {
            abort(403);
        }

        $path = $attachment->path;
        $message = $attachment->message;

        // Elimina prima da DB, poi da disk
        $attachment->delete();
        Storage::disk('local')->delete($path);

        // Se il messaggio resta senza testo e senza altri allegati, cancellalo
        $hasText = filled(trim($message->body ?? ''));
        $hasOtherAttachments = $message->attachments()->exists();
        $messageDeleted = false;

        if (!$hasText && !$hasOtherAttachments) {
            $message->delete();
            $messageDeleted = true;
        }

        return response()->json([
            'message_deleted' => $messageDeleted,
            'message_id' => $message->id,
            ]);
        }
}