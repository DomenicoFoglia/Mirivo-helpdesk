<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Aggiorna tema visivo
     */
    public function updateTheme(Request $request){
        $user = Auth::user();

        $validated = $request->validate([
            'theme' => 'required|in:amber,midnight,forest,ember,steel,crimson,violet,light,light-warm,light-green,royale'
        ]);

        $user->update(['theme' => $validated['theme']]);

        return response()->json(['theme' => $user->theme]);
    }

    /**
     * Aggiorna dati dell'utente (Nome, Cognome, Email)
     */
    public function updateProfile(Request $request ){
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => ['required', 'email:dns', Rule::unique('users')->ignore($user->id)]
        ]);

        $user->update($validated);

        return $user->load('company');
    }

    /**
     * Aggiorna password
     */
    public function updatePassword(Request $request){
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        // Invalida tutti i token tranne quello in uso: se l'utente è loggato anche
        // da altri device (browser, mobile), quelle sessioni vengono terminate.
        $user->tokens()
            ->where('id', '!=', $user->currentAccessToken()->id)
            ->delete();

        return response()->json([
            'message' => 'Password aggiornata con successo'
        ]);
    }
}
