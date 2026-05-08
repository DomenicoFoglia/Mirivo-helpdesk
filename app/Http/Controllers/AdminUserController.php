<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $users = User::where('company_id', $user->company_id)->paginate(15);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        $user = Auth::user();

        $updatedUser = User::where('company_id', $user->company_id)->findOrFail($id);

        if($updatedUser->id === $user->id)
            return response()->json([
                    'message' => 'No caro non puoi modificare te stesso, tu sei il boss'
            ], 403);

        $validated = $request->validate([
            'role' => 'required|in:agent,user',
            'level' => 'nullable|in:1,2'
        ]);

        if($validated['role'] === 'user')
            $validated['level'] = null;

        if($validated['role'] === 'agent' && $validated['level'] === null){
            return response()->json([
                'messaggio' => "Livello per il tecnico non selezionato"
            ], 422);
        }

        $updatedUser->update($validated);

        return response()->json($updatedUser);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $userToDelete = User::where('company_id', $user->company_id)->findOrFail($id);

        if($userToDelete->id === $user->id)
            return response()->json([
                    'message' => "No caro non puoi eliminare te stesso, senza di te fallirebbe l'azienda"
            ], 403);

        $userToDelete->delete();

        return response()->json([
            'message' => 'Utente eliminato con successo'
        ], 204);
    }
}
