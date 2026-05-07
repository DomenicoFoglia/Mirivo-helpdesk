<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;


class AuthController extends Controller
{
    public function register(Request $request){
        // Avvolgiamo la validazione e la creazione di azienda e utente in una transaction 
        // Ci permette di evitare che, in caso di fallimento, resti nel database qualche residuo.
        // Una trasazione database garantisce che un gruppo di operazioni vada a buon fine 
        // tuttee insiem,e oppure nessuna. Se qualcosa fallisce nel mezzo, viene annullato tutto 
        // e il database torna allo stato precedente ( un rollback praticamente)
        $newUser = DB::transaction(function() use ($request){
            $validated = $request->validate([
                'user.name' => 'required|string|max:255',
                'user.surname' => 'required|string|max:255',
                'user.email' => 'required|email|unique:users,email',
                'user.password' => [
                                    'required',
                                    'confirmed',
                                    Password::min(12)->mixedCase()->numbers()->symbols()
                                    ],
                'company.name' => 'required|string|max:255',
                'company.logo' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:2048'
            ]);

            // Salviamo il logo nello storage
            $logoPath = $request->file('company.logo')->store('logos', 'public');

            // Generiamo lo slug 
            // Aggiungiamo Str::random(6) per assicurarci che 2 aziende con lo stesso nome 
            // non abbiano lo stesso slug aggiungenddo un suffisso casuale
            $slug = Str::slug($validated['company']['name']) . '-' . Str::random(6);

            // Creiamo prima l'azienda cosi da poterla poi collegare all'utente
            $company = Company::create([
                'name' => $validated['company']['name'],
                'logo' => $logoPath,
                'slug' => $slug
            ]);

            $user = User::create([
                'name' => $validated['user']['name'],
                'surname' => $validated['user']['surname'],
                'email' => $validated['user']['email'],
                'password' => $validated['user']['password'],
                'role' => 'admin',
                'company_id' => $company->id
            ]);
            
            return ['user' => $user, 'company' => $company];
        });

        // Generiamo il token sanctum da restituire poi al frontend per evitare che
        // l'utente sia costretto a fare il login dopo la registrazione
        $token = $newUser['user']->createToken('auth_token')->plainTextToken;

        return response()->json([
                'message' => 'Utente e azienda creati correttamente',
                'company' => $newUser['company'],
                'user' => $newUser['user'],
                'token' => $token
            ], 201);
    }

    public function login(Request $request){

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(!Auth::attempt($credentials)){
            return response()->json([
                'message' => 'Dati non corretti'
            ], 401);
        }

        $user = User::find(Auth::id());

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout effettuato'
        ], 200);
    }


    public function registerByInvite(Request $request, string $authToken){
        // Cerco l'invito nel DB e faccio 3 controlli:
        // 1. Che il token passato sia effetivamente prensete nell'invito
        // 2. Che la data di scadenza dell'invito non sia stata gia superata
        // 3. Che accepted_at sia null, cioe' che l'invito non sia stato ancora accettato.
        $invitation = Invitation::where('token', $authToken)
                                ->where('expires_at', '>', now())
                                ->whereNull('accepted_at')
                                ->firstOrFail();

        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'password' => [
                                'required',
                                'confirmed',
                                Password::min(12)->mixedCase()->numbers()->symbols()
                                ], 
        ]);

        $user = User::create([
            'company_id' => $invitation->company_id,
            'email' => $invitation->email,
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'password' => $validated['password'],
            'role' => $invitation->role,
        ]);

        $invitation->accepted_at = now();
        $invitation->save();
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrazione avvenuta con successo',
            'user' => $user,
            'token' => $accessToken
        ], 201);
        
    }

}





