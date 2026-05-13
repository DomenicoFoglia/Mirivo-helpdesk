<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
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
}
