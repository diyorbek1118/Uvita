<?php

declare(strict_types=1);

namespace Modules\Seller\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Seller\Application\Handlers\SellerLoginHandler;
use Modules\Seller\Presentation\Requests\SellerLoginRequest;

final class SellerAuthController extends Controller
{
    public function login(SellerLoginRequest $request, SellerLoginHandler $handler): JsonResponse
    {
        return response()->json([
            'data' => $handler->handle(
                (string) $request->validated('phone'),
                (string) $request->validated('password'),
            ),
            'message' => 'Kirish muvaffaqiyatli',
        ]);
    }
}
