<?php
namespace App\Controllers;

use App\Services\AIService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChatController
{
    private $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function chat(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $message = $data['message'] ?? '';
        $sessionId = $data['session_id'] ?? null; 

        if (empty($message)) {
            $response->getBody()->write(json_encode(['error' => 'Message is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $result = $this->aiService->chatWithAI($sessionId, $message);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getHistory(Request $request, Response $response, array $args): Response
    {
        $sessionId = $args['session_id'] ?? null;

        if (empty($sessionId)) {
            $response->getBody()->write(json_encode(['history' => []]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        try {
            $rawHistory = $this->aiService->getHistoryBySessionId($sessionId);
            
            $cleanHistory = [];
            foreach ($rawHistory as $msg) {

                if ($msg['role'] === 'function') continue;

                if ($msg['role'] === 'model' && isset($msg['parts'][0]['functionCall'])) {
                    continue; 
                }

                $cleanHistory[] = [
                    'role' => $msg['role'],
                    'text' => $msg['parts'][0]['text'] ?? ''
                ];
            }

            $response->getBody()->write(json_encode(['history' => $cleanHistory]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

     private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $response->getBody()->write($json);
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}