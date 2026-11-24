<?php

namespace App\Http\Controllers;

use App\Services\PublicStorefrontAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicChatController extends Controller
{
    public function __invoke(Request $request, PublicStorefrontAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:400'],
        ], [
            'message.required' => 'Escribe una consulta antes de enviar.',
            'message.max' => 'La consulta no debe superar los 400 caracteres.',
        ]);

        return response()->json($assistant->respond($data['message']));
    }
}
