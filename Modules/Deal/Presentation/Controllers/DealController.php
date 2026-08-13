<?php

declare(strict_types=1);

namespace Modules\Deal\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Deal\Application\Commands\CancelDealCommand;
use Modules\Deal\Application\Commands\CompleteDealCommand;
use Modules\Deal\Application\Commands\ConfirmDealCommand;
use Modules\Deal\Application\Commands\CreateDealCommand;
use Modules\Deal\Application\Handlers\CancelDealHandler;
use Modules\Deal\Application\Handlers\CompleteDealHandler;
use Modules\Deal\Application\Handlers\ConfirmDealHandler;
use Modules\Deal\Application\Handlers\CreateDealHandler;
use Modules\Deal\Application\Handlers\GetIncomingDealsHandler;
use Modules\Deal\Application\Handlers\GetOutgoingDealsHandler;
use Modules\Deal\Application\Queries\GetIncomingDealsQuery;
use Modules\Deal\Application\Queries\GetOutgoingDealsQuery;
use Modules\Listing\Infrastructure\Persistence\Models\ListingModel;
use Modules\Deal\Presentation\Requests\CreateDealRequest;
use Modules\Deal\Presentation\Resources\DealResource;

final class DealController extends Controller
{
    public function __construct(
        private readonly CreateDealHandler $createHandler,
        private readonly ConfirmDealHandler $confirmHandler,
        private readonly CompleteDealHandler $completeHandler,
        private readonly CancelDealHandler $cancelHandler,
        private readonly GetIncomingDealsHandler $getIncomingHandler,
        private readonly GetOutgoingDealsHandler $getOutgoingHandler,
    ) {}

    public function store(CreateDealRequest $request): JsonResponse
    {
        $listing = ListingModel::findOrFail((int) $request->input('listing_id'));
        $total   = (int) round($listing->price * (float) $request->input('quantity'));

        $deal = $this->createHandler->handle(
            CreateDealCommand::fromRequest($request, auth()->id(), $total)
        );

        return DealResource::make($deal)
            ->additional(['message' => 'Buyurtma yuborildi'])
            ->response()
            ->setStatusCode(201);
    }

    public function incoming(Request $request): JsonResponse
    {
        $deals = $this->getIncomingHandler->handle(
            new GetIncomingDealsQuery(auth()->id(), (int) $request->input('per_page', 20))
        );

        return DealResource::collection($deals)->response();
    }

    public function outgoing(Request $request): JsonResponse
    {
        $deals = $this->getOutgoingHandler->handle(
            new GetOutgoingDealsQuery(auth()->id(), (int) $request->input('per_page', 20))
        );

        return DealResource::collection($deals)->response();
    }

    public function confirm(int $id): JsonResponse
    {
        $deal = $this->confirmHandler->handle(new ConfirmDealCommand($id, auth()->id()));

        return DealResource::make($deal)->additional(['message' => 'Buyurtma tasdiqlandi'])->response();
    }

    public function complete(int $id): JsonResponse
    {
        $deal = $this->completeHandler->handle(new CompleteDealCommand($id, auth()->id()));

        return DealResource::make($deal)->additional(['message' => 'Bitim yakunlandi'])->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $deal = $this->cancelHandler->handle(new CancelDealCommand($id, auth()->id()));

        return DealResource::make($deal)->additional(['message' => 'Bitim bekor qilindi'])->response();
    }
}
