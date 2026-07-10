<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;

class GeminiService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $model = config('services.gemini.model');
        $this->apiKey = config('services.gemini.key');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    public function chat(
        string $userMessage,
        array $history,
        Collection $faqs,
        Collection $categories
    ): array {
        $prompt = $this->buildPrompt($userMessage, $history, $faqs, $categories);

        try {
            $response = Http::timeout(30)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $this->responseSchema(),
                    'temperature' => 0.3,
                ],
            ]);
        } catch (ConnectionException $e) {
            Log::error('Gemini connection error: ' . $e->getMessage());
            throw new \Exception('Servizio AI non raggiungibile. Riprova tra poco.');
        }

        if (!$response->successful()) {
            $code = $response->json('error.code') ?? $response->status();
            $msg = $response->json('error.message') ?? 'Errore sconosciuto';

            if ($code === 503) {
                throw new \Exception('Il servizio AI è temporaneamente sovraccarico. Riprova tra qualche minuto.');
            }

            Log::error('Gemini error: ' . $response->body());
            throw new \Exception('Errore AI: ' . $msg);
        }

        // la richiesta riesce ma il contenuto è strano
        // Esempi: Gemini risponde 200 OK ma il campo che cerchiamo è vuoto, 
        // oppure il testo non è JSON valido nonostante lo schema, 
        // oppure il JSON è valido ma manca dei campi obbligatori.
        $rawText = $response->json('candidates.0.content.parts.0.text');

        if (!$rawText) {
            Log::error('Gemini empty response: ' . $response->body());
            throw new \Exception('Risposta AI vuota.');
        }

        $parsed = json_decode($rawText, true);

        if (!is_array($parsed) || !isset($parsed['type'], $parsed['message'])) {
            Log::error('Gemini malformed JSON: ' . $rawText);
            throw new \Exception('Risposta AI in formato non valido.');
        }

        return $parsed;
    }

    /**
     * Genera una bozza di FAQ a partire da un ticket chiuso.
     * Non salva niente in DB: ritorna solo la bozza da mostrare all'admin.
     *
     * Sanitizza suggested_category_id e similar_faq_id contro le collezioni
     * fornite: Gemini a volte inventa ID, qui li forziamo a null se non
     * appartengono alla company.
     *
     * @throws \Exception per qualsiasi errore lato Gemini (rete, HTTP, JSON).
     */
    public function generateFaqDraft(
        Ticket $ticket,
        Collection $categories,
        Collection $existingFaqs,
        ?string $adminSummary = null
    ): array {
        $apiKey = config('services.gemini.key');
        $model  = config('services.gemini.model', 'gemini-2.5-flash');

        if (blank($apiKey)) {
            throw new \Exception('Servizio AI non configurato.');
        }

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'question'              => ['type' => 'STRING'],
                'answer'                => ['type' => 'STRING'],
                'similar_faq_id'        => ['type' => 'INTEGER', 'nullable' => true],
                'similar_faq_reason'    => ['type' => 'STRING',  'nullable' => true],
            ],
            'required' => ['question', 'answer'],
        ];

        $payload = [
            'contents' => [
                ['parts' => [['text' => $this->buildFaqDraftPrompt($ticket, $categories, $existingFaqs, $adminSummary)]]],
            ],
            'generationConfig' => [
                'temperature'      => 0.3,
                'responseMimeType' => 'application/json',
                'responseSchema'   => $schema,
            ],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(30)->post($url, $payload);
        } catch (ConnectionException $e) {
            Log::error('Gemini connection error (faq draft): ' . $e->getMessage());
            throw new \Exception('Servizio AI non raggiungibile, riprova tra qualche istante.');
        }

        if (!$response->successful()) {
            $status = $response->status();
            Log::error("Gemini HTTP {$status} (faq draft): " . $response->body());
            if ($status === 503) {
                throw new \Exception('Il servizio AI e\' sovraccarico, riprova fra poco.');
            }
            throw new \Exception('Errore dal servizio AI (HTTP ' . $status . ').');
        }

        $rawText = $response->json('candidates.0.content.parts.0.text');
        if (blank($rawText)) {
            Log::error('Gemini empty response (faq draft): ' . $response->body());
            throw new \Exception('Risposta AI vuota.');
        }

        $parsed = json_decode($rawText, true);
        if (!is_array($parsed) || !isset($parsed['question'], $parsed['answer'])) {
            Log::error('Gemini malformed JSON (faq draft): ' . $rawText);
            throw new \Exception('Risposta AI in formato non valido.');
        }

        $similarFaqId = $parsed['similar_faq_id'] ?? null;
        if ($similarFaqId !== null && !$existingFaqs->pluck('id')->contains($similarFaqId)) {
            $similarFaqId = null;
        }

        $similarFaqReason = $similarFaqId !== null
            ? ($parsed['similar_faq_reason'] ?? null)
            : null;

        return [
            'question'              => trim(mb_substr($parsed['question'], 0, 255)),
            'answer'                => trim(mb_substr($parsed['answer'], 0, 3000)),
            'similar_faq_id'        => $similarFaqId,
            'similar_faq_reason'    => $similarFaqReason,
        ];
    }

    /**
     * Costruisce il prompt per generateFaqDraft.
     * Isolato in metodo privato per leggibilita' e per facilitare l'iterazione
     * sul prompt senza toccare la logica HTTP/parsing.
     */
    private function buildFaqDraftPrompt(
        Ticket $ticket,
        Collection $categories,
        Collection $existingFaqs,
        ?string $adminSummary
    ): string {
        $title = $ticket->title;
        $currentCategory = $ticket->category?->name ?? 'Non specificata';

        // Solo messaggi pubblici: le note interne agent-agent non devono finire
        // nel prompt. Ordinamento cronologico gia' garantito dal controller.
        $messagesText = $ticket->messages
            ->where('type', 'public')
            ->map(function ($m) {
                $body = trim($m->body ?? '');
                if ($body === '') {
                    return null;
                }
                $role   = $m->user?->role ?? 'sistema';
                $author = trim(($m->user?->name ?? '') . ' ' . ($m->user?->surname ?? '')) ?: 'Utente';
                return "[{$role}] {$author}: {$body}";
            })
            ->filter()
            ->implode("\n---\n");

        $categoriesText = $categories
            ->map(fn($c) => "[ID {$c->id}] {$c->name}")
            ->implode("\n");

        $existingFaqsText = $existingFaqs->isEmpty()
            ? '(nessuna FAQ presente al momento)'
            : $existingFaqs
                ->map(fn($f) => "[FAQ {$f->id}] D: {$f->question}\n         R: " . mb_substr($f->answer, 0, 200))
                ->implode("\n\n");

        $adminBlock = filled($adminSummary)
            ? <<<BLOCK

    RIASSUNTO DELL'ADMIN (fonte primaria di verita', priorita' sul thread)
    {$adminSummary}

    BLOCK
            : '';

        return <<<PROMPT
    Sei Mira, l'assistente di un helpdesk aziendale. Il tuo compito e' trasformare un ticket chiuso in una voce di FAQ riusabile per la knowledge base.

    TICKET
    Titolo: {$title}
    Categoria attuale: {$currentCategory}

    CONVERSAZIONE (ordine cronologico, ruoli tra parentesi quadre)
    {$messagesText}
    {$adminBlock}
    CATEGORIE DISPONIBILI (usa uno di questi ID per suggested_category_id, oppure null se nessuna e' pertinente)
    {$categoriesText}

    FAQ GIA' ESISTENTI NELLA KNOWLEDGE BASE
    {$existingFaqsText}

    ISTRUZIONI
    1. Estrai il problema di fondo, non il caso singolo. Rimuovi nomi propri, email, numeri d'ordine, versioni specifiche di software, date, riferimenti temporali ("ieri", "stamattina"), dettagli personali. La FAQ deve valere per chiunque, in qualsiasi momento.
    2. Riscrivi la domanda in forma impersonale (esempi: "Come faccio a...", "Perche' non riesco a...", "Cosa succede quando...").
    3. Riscrivi la risposta in forma impersonale e chiara, come un tecnico che documenta la soluzione. Preserva i passi numerati se presenti nel dialogo, prosa scorrevole altrimenti.
    4. Rispondi sempre in italiano.
    5. Le categorie sono elencate sopra solo come contesto semantico per aiutarti a capire come e' organizzata la knowledge base. Non devi scegliere una categoria: sara' ereditata dal ticket o scelta dall'admin
    6. Se una delle FAQ gia' esistenti copre lo stesso problema di fondo, metti il suo ID in similar_faq_id e spiega brevemente in similar_faq_reason perche' sono simili (max 150 caratteri). Altrimenti metti null in entrambi. Non essere troppo severo: due FAQ che sfiorano lo stesso argomento ma trattano problemi diversi NON sono simili.
    7. Limiti: question massimo 255 caratteri, answer massimo 3000 caratteri.

    Rispondi solo con il JSON conforme allo schema. Nessun testo prima o dopo.
    PROMPT;
    }

    private function buildPrompt(
        string $userMessage,
        array $history,
        Collection $faqs,
        Collection $categories
    ): string {
        $faqsText = $faqs->isEmpty()
            ? '(Nessuna FAQ disponibile)'
            : $faqs->map(fn($f) => "[FAQ {$f->id}] D: {$f->question}\nR: {$f->answer}")->join("\n\n");

        $categoriesText = $categories->isEmpty()
            ? '(Nessuna categoria disponibile)'
            : $categories->map(fn($c) => "[ID {$c->id}] {$c->name}")->join("\n");

        $historyText = empty($history)
            ? '(Nessuna conversazione precedente)'
            : collect($history)
                ->map(fn($m) => ($m['role'] === 'user' ? 'Utente' : 'Assistente') . ': ' . $m['content'])
                ->join("\n");

        return <<<PROMPT
Sei Mira, l'assistente virtuale di un helpdesk aziendale chiamato Mirivo. Il tuo compito è aiutare gli utenti rispondendo alle loro domande basandoti ESCLUSIVAMENTE sulle FAQ aziendali qui sotto. Non inventare informazioni. Se l'utente ti saluta o chiede chi sei, presentati come Mira.

FAQ DISPONIBILI:
{$faqsText}

CATEGORIE DI TICKET DISPONIBILI:
{$categoriesText}

CRONOLOGIA CONVERSAZIONE:
{$historyText}

MESSAGGIO ATTUALE DELL'UTENTE:
{$userMessage}

ISTRUZIONI:
- Se la domanda è coperta da una o più FAQ, rispondi citando le informazioni rilevanti. In questo caso usa type="deflection" e lascia ticket_suggestion a null.
- Se la domanda NON è coperta dalle FAQ, suggerisci di aprire un ticket. Usa type="escalation", fornisci un messaggio empatico che invita ad aprire un ticket, e in ticket_suggestion proponi un titolo conciso (max 80 caratteri) e l'id della categoria più pertinente dalla lista. Se nessuna categoria è chiaramente pertinente, metti category_id a null.
- Rispondi sempre in italiano.
- Sii conciso ma cordiale.
PROMPT;
    }

    private function responseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'type' => [
                    'type' => 'STRING',
                    'enum' => ['deflection', 'escalation'],
                ],
                'message' => ['type' => 'STRING'],
                'ticket_suggestion' => [
                    'type' => 'OBJECT',
                    'nullable' => true,
                    'properties' => [
                        'title' => ['type' => 'STRING'],
                        'category_id' => [
                            'type' => 'INTEGER',
                            'nullable' => true,
                        ],
                    ],
                ],
            ],
            'required' => ['type', 'message'],
        ];
    }
}