<?php

namespace App\Middleware;

use App\Config\Settings;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

class TurnstileMiddleware
{
    public function __invoke(Request $request, Handler $handler): \Psr\Http\Message\ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $token = $parsedBody['turnstileToken'] ?? $parsedBody['cf-turnstile-response'] ?? null;

        if (!$token) {
            return $this->jsonResponse(new Response(), 400, ['error' => 'Vui lòng xác nhận CAPTCHA']);
        }

        $secretKey = Settings::get('TURNSTILE_SECRET_KEY');
        if (!$secretKey) {
            error_log("TurnstileMiddleware: Thiếu TURNSTILE_SECRET_KEY trong cấu hình");
            return $this->jsonResponse(new Response(), 500, ['error' => 'Lỗi cấu hình hệ thống']);
        }

        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        
        $data = [
            'secret'   => $secretKey,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($verifyUrl, false, $context);

        if ($result === false) {
            error_log("TurnstileMiddleware: Lỗi gọi API Cloudflare");
            return $this->jsonResponse(new Response(), 500, ['error' => 'Không thể kết nối đến hệ thống xác minh']);
        }

        $responseData = json_decode($result, true);

        if (!$responseData || !isset($responseData['success']) || !$responseData['success']) {
            $cfErrors = isset($responseData['error-codes']) ? implode(', ', $responseData['error-codes']) : 'unknown';
            error_log("TurnstileMiddleware: Token không hợp lệ. Response: " . $result);
            return $this->jsonResponse(new Response(), 400, ['error' => 'Xác minh CAPTCHA thất bại (' . $cfErrors . ')']);
        }

        return $handler->handle($request);
    }

    private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json);
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json;charset=utf-8');
    }
}
