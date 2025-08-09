<?php
declare(strict_types=1);

namespace App\Support;

final class JsonResponse
{
    public static function send(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
} 