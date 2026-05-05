<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        // Filtriamo direttamente per company_id senza caricare il model Company in memoria.
        // $user->company_id legge la colonna FK dall'utente autenticato (1 query invece di 2).
        // Garantisce l'isolamento multi-tenant: ogni utente vede solo le FAQ della propria azienda.
        $faqs = Faq::where('company_id', $user->company_id)->paginate(15);
        
        return response()->json([
            'faqs' => $faqs
        ]);
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

        $faq = Faq::where('company_id', $user->company_id)->findOrFail($id);

        return response()->json($faq);
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
        //
    }
}
