<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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