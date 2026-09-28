<?php

namespace App\Exceptions;

use App\Models\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // API で存在しないお問い合わせを指定されたときは、決まった形の 404 を返す
        $this->renderable(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            $previous = $e->getPrevious();

            if ($request->is('api/*')
                && $previous instanceof ModelNotFoundException
                && $previous->getModel() === Contact::class) {
                return response()->json(['error' => 'お問い合わせが見つかりませんでした。'], 404);
            }

            return null;
        });
    }

    /**
     * API へのリクエストは、Accept ヘッダーがなくてもエラーを JSON で返す
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }
}
