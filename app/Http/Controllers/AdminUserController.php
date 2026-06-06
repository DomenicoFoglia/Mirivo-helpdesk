<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $users = User::where('company_id', $user->company_id)
            ->when($request->filled('role'), function($query) use ($request){
                $query->where('role', $request->role);
            })
            ->when($request->filled('level'), function($query) use ($request){
                $query->where('level', $request->level);
            })
            ->when($request->filled('search'), function($query) use ($request){
                $query->where(function($q) use ($request){
                    $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('surname', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            })
            ->paginate(15);

        return $users;
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
        $user = Auth::user();

        $userToShow = User::where('company_id', $user->company_id)
            ->findOrFail($id);

        return $userToShow;
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
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($updatedUser->id)],
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

        return $updatedUser;
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

        return response()->noContent();
    }

    /**
     * Resetta password.
     */
    public function resetPassword(string $id)
    {
        $admin = Auth::user();

        $userToReset = User::where('company_id', $admin->company_id)
            ->findOrFail($id);

        Password::sendResetLink(['email' => $userToReset->email]);

        return response()->json(['message' => 'Email di reset inviata']);
    }




}
