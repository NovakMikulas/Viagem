<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response;
use ErrorException;
use InvalidArgumentException;
use Throwable;

class ErrorHandlerMiddleware
{
    public function handle(callable $next): void
    {
        ini_set('display_errors', '0');
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $next();
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            error_log((string) $e);
            Response::error('Interní chyba serveru.', 500);
        }
    }
}
