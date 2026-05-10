<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function updateTheme(Request $request){
        $user = Auth::user();

        $validated = $request->validate([
            'theme' => 'required|in:amber,midnight,forest,ember,steel,crimson,violet,light,light-warm,light-green,royale'
        ]);

        $user->update(['theme' => $validated['theme']]);

        return response()->json(['theme' => $user->theme]);
    }
}
