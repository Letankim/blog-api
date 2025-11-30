<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Exception\HttpException;
use Slim\Psr7\Response;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler
{
    private LoggerInterface $logger;
    private bool $displayErrorDetails;
    private bool $logErrors;
    private bool $logErrorDetails;
    private string $appEnv;

    public function __construct(
        LoggerInterface $logger,
        bool $displayErrorDetails = false,
        bool $logErrors = true,
        bool $logErrorDetails = true,
        string $appEnv = 'development'
    ) {
        $this->logger = $logger;
        $this->displayErrorDetails = $displayErrorDetails;
        $this->logErrors = $logErrors;
        $this->logErrorDetails = $logErrorDetails;
        $this->appEnv = $appEnv;
    }

    public function __invoke(Request $request, Handler $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($request, $exception);
        }
    }

    private function handleException(Request $request, Throwable $exception): ResponseInterface
    {
        $statusCode = $exception instanceof HttpException ? $exception->getCode() : 500;
        $statusCode = $statusCode >= 400 && $statusCode < 600 ? $statusCode : 500;

        if ($this->logErrors) {
            $logMessage = $this->logErrorDetails
                ? $this->formatDetailedLog($exception, $request)
                : $exception->getMessage();

            match ($statusCode) {
                401 => $this->logger->warning($logMessage),
                404 => $this->logger->info($logMessage),
                405 => $this->logger->info($logMessage),
                default => $this->logger->error($logMessage),
            };
        }

        $response = new Response();

        if ($this->displayErrorDetails && $this->appEnv !== 'production') {
            $payload = [
                'error' => $this->getEnglishErrorMessage($statusCode, $exception),
                'details' => [
                    'type' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
            ];
        } else {
            $payload = [
                'error' => $this->getEnglishErrorMessage($statusCode, $exception)
            ];
        }

        return $this->jsonResponse($response, $statusCode, $payload);
    }

    private function getEnglishErrorMessage(int $statusCode, Throwable $exception): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => $exception->getMessage() ?: 'An unexpected error occurred',
        };
    }

    private function formatDetailedLog(Throwable $exception, Request $request): string
    {
        $context = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'body' => $request->getParsedBody() ? json_encode($request->getParsedBody(), JSON_UNESCAPED_UNICODE) : null,
        ];

        return sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s\nRequest: %s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString(),
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function jsonResponse(Response $response, int $status, array $data): ResponseInterface
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $response->getBody()->write($json);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}