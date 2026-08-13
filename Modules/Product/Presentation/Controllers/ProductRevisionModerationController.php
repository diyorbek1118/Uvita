<?php

declare(strict_types=1);

namespace Modules\Product\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Product\Application\Handlers\ReviewSellerProductRevisionHandler;
use Modules\Product\Infrastructure\Persistence\Models\ProductRevision;
use Modules\Product\Presentation\Resources\ProductRevisionResource;

final class ProductRevisionModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $revisions = ProductRevision::query()
            ->with(['product', 'seller'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return ProductRevisionResource::collection($revisions)->response();
    }

    public function approve(int $revision, ReviewSellerProductRevisionHandler $handler): JsonResponse
    {
        return ProductRevisionResource::make($handler->approve($revision, $this->reviewer()->id))
            ->additional(['message' => 'Mahsulot versiyasi tasdiqlandi va marketga chiqarildi'])
            ->response();
    }

    public function reject(int $revision, Request $request, ReviewSellerProductRevisionHandler $handler): JsonResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        return ProductRevisionResource::make($handler->reject($revision, $this->reviewer()->id, $validated['reason']))
            ->additional(['message' => 'Mahsulot versiyasi rad etildi'])
            ->response();
    }

    private function reviewer(): Staff
    {
        /** @var Staff $reviewer */
        $reviewer = auth('sanctum')->user();
        return $reviewer;
    }
}
