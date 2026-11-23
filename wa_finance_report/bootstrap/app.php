<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsureApiAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'active.account' => EnsureActiveAccount::class,
            'api.access' => EnsureApiAccess::class,
            'active.subscription' => EnsureActiveSubscription::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return ApiResponse::error('Validation failed.', $exception->errors(), 422);
            }

            if ($exception instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated.', status: 401);
            }

            if ($exception instanceof AuthorizationException) {
                return ApiResponse::error('This action is unauthorized.', status: 403);
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = match ($status) {
                    404 => 'Resource not found.',
                    402 => 'Active subscription required.',
                    429 => 'Too many requests.',
                    default => 'Request failed.',
                };

                return ApiResponse::error($message, status: $status);
            }

            return ApiResponse::error('Internal server error.', status: 500);
        });
    })->create();
