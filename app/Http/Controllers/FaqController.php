<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Ticket;
use App\Models\Category;
use App\Services\GeminiService;

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
        $faqs = Faq::where('company_id', $user->company_id)->with('category')->get();
        
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
        
        $faq->load('category');

        return response()->json($faq, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $faq = Faq::where('company_id', $user->company_id)->with('category')->findOrFail($id);

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
        $faq->load('category');

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

    /**
     * Genera una bozza di FAQ da un ticket chiuso tramite Gemini.
     * Non salva niente in DB: ritorna la bozza al frontend che la mostra
     * pre-compilata, l'admin la conferma con POST /api/admin/faqs.
     *
     * Guard su status='closed' PRIMA della chiamata AI: risparmia quota
     * Gemini se il ticket non e' eleggibile.
     *
     * Errori del service (rete/HTTP/JSON) tradotti in 503 con messaggio
     * leggibile per il toast lato frontend.
     */
    public function draftFromTicket(Request $request, int $ticketId, GeminiService $gemini)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'admin_summary' => 'sometimes|nullable|string|max:2000',
        ]);

        $ticket = Ticket::where('company_id', $user->company_id)
            ->with([
                'messages' => fn($q) => $q->where('type', 'public')->orderBy('created_at'),
                'messages.user:id,name,surname,role',
                'category:id,name',
            ])
            ->findOrFail($ticketId);

        if ($ticket->status !== 'closed') {
            return response()->json([
                'message' => 'Solo i ticket chiusi possono essere trasformati in FAQ.',
            ], 422);
        }

        $categories = Category::where('company_id', $user->company_id)
            ->select('id', 'name')
            ->get();

        $existingFaqs = Faq::where('company_id', $user->company_id)
            ->select('id', 'question', 'answer')
            ->get();

        try {
            $draft = $gemini->generateFaqDraft(
                $ticket,
                $categories,
                $existingFaqs,
                $validated['admin_summary'] ?? null
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json([
            'question'              => $draft['question'],
            'answer'                => $draft['answer'],
            'suggested_category_id' => $ticket->category_id,
            'similar_faq_id'        => $draft['similar_faq_id'],
            'similar_faq_reason'    => $draft['similar_faq_reason'],
            'ticket_id'             => $ticket->id,
        ]);
    }
}
