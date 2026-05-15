<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentDashboardController extends Controller
{
    public function stats(){
        $user = Auth::user();

        $closedTicketsToday = Ticket::where('company_id', $user->company_id)
                                    ->where('assignee_id', $user->id)
                                    ->whereToday('closed_at')
                                    ->count();
        
        return response()->json([
            'closedTicketsToday' => $closedTicketsToday
        ]);

    }
}
