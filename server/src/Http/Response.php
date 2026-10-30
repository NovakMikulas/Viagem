<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $message, int $status): void
    {
        self::json(['error' => $message], $status);
    }

    public static function vectorTile(string $tile): void
    {
        header('Content-Type: application/vnd.mapbox-vector-tile');
        header('Cache-Control: public, max-age=3600');
        echo $tile;
    }
}
