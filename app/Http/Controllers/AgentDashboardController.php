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

        $closedTicketsWeek = Ticket::where('company_id', $user->company_id)
                                    ->where('assignee_id', $user->id)
                                    ->where('closed_at', '>', now()->subDays(7))
                                    ->selectRaw('DATE(closed_at) as date, COUNT(*) as count')
                                    ->groupBy('date')
                                    ->orderBy('date')
                                    ->get();
        
        $closedTicketsWeekArray = [];

        for( $i = 6; $i >= 0; --$i){
            
            $day = now()->subDays($i)->format('Y-m-d');

            $found = $closedTicketsWeek->firstWhere('date', $day);
            if($found){
                $closedTicketsWeekArray[] = $found->count;
            }else{
                $closedTicketsWeekArray[] = 0;
            }
        }


        return response()->json([
            'closedTicketsToday' => $closedTicketsToday,
            'closedTicketsWeek' => $closedTicketsWeekArray
        ]);

    }
}
