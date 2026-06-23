<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Application\DTOs\StaffLoginDTO;
use Modules\Admin\Application\Handlers\StaffLoginHandler;
use Modules\Admin\Presentation\Requests\StaffLoginRequest;

final class StaffAuthController extends Controller
{
    public function __construct(
        private readonly StaffLoginHandler $loginHandler,
    ) {}

    public function login(StaffLoginRequest $request): JsonResponse
    {
        $result = $this->loginHandler->handle(
            StaffLoginDTO::fromRequest($request)
        );

        return response()->json([
            'data'    => $result,
            'message' => 'Kirish muvaffaqiyatli',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        auth('sanctum')->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Chiqish muvaffaqiyatli']);
    }
}
