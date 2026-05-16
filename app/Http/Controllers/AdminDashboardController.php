<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Invitation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function stats(){
        $user = Auth::user();

        $openTickets = Ticket::where('company_id', $user->company_id)->where('status', 'open')->count();

        $workingTickets = Ticket::where('company_id', $user->company_id)->where('status', 'working')->count();

        $closedTicketsToday = Ticket::where('company_id', $user->company_id)->whereToday('closed_at')->count();

        $withoutAnswerTickets = Ticket::where('company_id', $user->company_id)
                                ->where('status', '!=', 'closed' )
                                ->where('updated_at', '<', now()->subHours(24))
                                ->count();

        return response()->json([
            'openTickets' => $openTickets,
            'workingTickets' => $workingTickets,
            'closedTicketsToday' => $closedTicketsToday,
            'withoutAnswerTickets' => $withoutAnswerTickets
        ]);
    }

    public function details(){
        $user = Auth::user();

        $attentionTickets = Ticket::select(['id', 'title', 'priority', 'updated_at'])
                                    ->where('company_id', $user->company_id)
                                    ->where('status', '!=', 'closed')
                                    ->where('updated_at', '<', now()->subHours(24))
                                    ->get();
        
        $agents = User::select(['id', 'name', 'level'])
                        ->where('company_id', $user->company_id)->where('role', 'agent')
                        ->withCount(['assigneeTickets' => function ($query) {
                            $query->where('status', '!=', 'closed');
                        }])->get();

        $pendingInvitations = Invitation::select(['email', 'role', 'created_at'])
                                        ->where('company_id',  $user->company_id)
                                        ->whereNull('accepted_at')
                                        ->where('expires_at', '>', now())
                                        ->get();

        $recentFaqs = Faq::select(['id', 'question', 'created_at'])
                    ->where('company_id', $user->company_id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

        return response()->json([
            'attentionTickets' => $attentionTickets,
            'agents' => $agents,
            'pendingInvitations' => $pendingInvitations,
            'recentFaqs' => $recentFaqs
        ]);
    }
}
