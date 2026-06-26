<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function ask(Request $request, GeminiService $gemini): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'sometimes|array|max:20',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
        ]);

        $user = $request->user();
        $companyId = $user->company_id;

        $faqs = Faq::where('company_id', $companyId)
            ->select('id', 'question', 'answer')
            ->get();

        $categories = Category::where('company_id', $companyId)
            ->select('id', 'name')
            ->get();

        $history = collect($validated['history'] ?? [])->take(-10)->values()->all();

        try {
            $result = $gemini->chat(
                $validated['message'],
                $history,
                $faqs,
                $categories
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 503);
        }

        return response()->json($result);
    }
}