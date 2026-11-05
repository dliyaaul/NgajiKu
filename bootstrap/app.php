<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        ]);

        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Model tidak ditemukan di API: jangan bocorkan nama kelas model.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*') && $exception->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Data tidak ditemukan.'], 404);
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Halaman galat bergaya aplikasi. 403/404 selalu; 500/503 hanya bila debug mati
        // agar detail galat tetap terlihat saat pengembangan.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();
            $tampilkan = in_array($status, [403, 404], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'));

            if (! $tampilkan || $request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            return Inertia::render('errors/error', [
                'status' => $status,
                'message' => $status === 403 ? $exception->getMessage() : null,
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
