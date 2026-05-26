<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determina se l'utente può vedere il ticket.
     *
     * Regole:
     * - Multi-tenant: deve essere nella stessa company
     * - Se è 'user', deve essere il proprietario del ticket
     * - Agent e admin vedono tutti i ticket della company
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Multi-tenant: company diversa, accesso negato
        if ($user->company_id !== $ticket->company_id) {
            return false;
        }

        // User vede solo i propri ticket
        if ($user->role === 'user' && $ticket->user_id !== $user->id) {
            return false;
        }

        // Agent e admin: ok (purché stessa company, già verificato sopra)
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return false;
    }
}
