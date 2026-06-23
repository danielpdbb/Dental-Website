<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Answer a visitor's chat message. Public endpoint used by the floating widget.
     */
    public function reply(Request $request, ChatbotService $bot): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($bot->answer($data['message']));
    }
}
