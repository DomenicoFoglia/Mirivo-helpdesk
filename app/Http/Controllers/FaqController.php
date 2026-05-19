<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        
        return $faqs;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:3000',
            'category_id' => ['required', Rule::exists('categories', 'id')->where('company_id', $user->company_id)],
        ]);

        $faq = Faq::create([
                'company_id' => $user->company_id,
                'category_id' => $validated['category_id'],
                'question' => $validated['question'],
                'answer' => $validated['answer']
            ]);

        return response()->json($faq, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $faq = Faq::where('company_id', $user->company_id)->findOrFail($id);

        return $faq;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        $faq = Faq::where('company_id', $user->company_id)->findOrFail($id);

        $validated = $request->validate([
            'question' => 'sometimes|string|max:255',
            'answer' => 'sometimes|string|max:3000',
            'category_id' => ['sometimes', Rule::exists('categories', 'id')->where('company_id', $user->company_id)],
        ]);

        $faq->update($validated);

        return $faq;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $faq = Faq::where('company_id', $user->company_id)->findOrFail($id);

        $faq->delete();

        return response()->noContent();
    }
}
