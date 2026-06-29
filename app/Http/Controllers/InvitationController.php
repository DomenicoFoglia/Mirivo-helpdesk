<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;

class InvitationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $invitations = Invitation::where('company_id', $user->company_id)->orderBy('created_at', 'desc')->paginate(15);

        return $invitations;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user  = Auth::user();

        $validated = $request->validate([
            'email' => [
                'required',
                'email:dns',
                'max:255',
                Rule::unique('invitations', 'email')->where(function ($query) {
                    return $query->whereNull('accepted_at')
                                ->where('expires_at', '>', now());
                }),
            ],
            'role' => 'required|in:agent,user',
        ]);

        $token = Str::random(32);

        $invite = Invitation::create([
            'company_id' => $user->company_id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => $token,
            'expires_at' => now()->addDays(7)
        ]);

        Mail::to($invite->email)->send(new InvitationMail($invite));

        return response()->json($invite, 201);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $invitation = Invitation::where('company_id', $user->company_id)->findOrFail($id);

        $invitation->delete();

        return response()->noContent();
    }
}
