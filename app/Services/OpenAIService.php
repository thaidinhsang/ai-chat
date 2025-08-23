<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Facades\Http;


class OpenAIService
{
    public function chat($pageId, string $content, array $systemDirectives = []): string
    {
        $pageData = Page::where('page_id', $pageId)->first();
        $apiKey = $pageData->openai_key ?? config('services.openai.key');
        $model = config('services.openai.model', 'gpt-4o-mini');

        
        $messages = [];
        foreach ($systemDirectives as $d) {
            $messages[] = ['role' => 'system', 'content' => $d];
        }
        $messages[] = ['role' => 'user', 'content' => $content];


        $res = Http::withOptions([
                'verify' => false,
            ])->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.5, 
            ]);


        if (!$res->ok()) {
            throw new \RuntimeException('OpenAI error: ' . $res->body());
        }
        $data = $res->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }
}
