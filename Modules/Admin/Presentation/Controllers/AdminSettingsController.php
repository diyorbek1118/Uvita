<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Application\Commands\UpdateSettingCommand;
use Modules\Admin\Application\Commands\UpdateSettingsCommand;
use Modules\Admin\Application\DTOs\UpdateSettingDTO;
use Modules\Admin\Application\Handlers\GetAllSettingsHandler;
use Modules\Admin\Application\Handlers\UpdateSettingHandler;
use Modules\Admin\Application\Handlers\UpdateSettingsHandler;
use Modules\Admin\Application\Queries\GetAllSettingsQuery;
use Modules\Admin\Presentation\Requests\UpdateSettingRequest;
use Modules\Admin\Presentation\Requests\UpdateSettingsRequest;

class AdminSettingsController
{
    public function __construct(
        private readonly GetAllSettingsHandler  $getAllHandler,
        private readonly UpdateSettingHandler   $updateOneHandler,
        private readonly UpdateSettingsHandler  $updateManyHandler,
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->getAllHandler->handle(new GetAllSettingsQuery());

        return response()->json(['data' => $data]);
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $this->updateOneHandler->handle(
            new UpdateSettingCommand(UpdateSettingDTO::fromRequest($request))
        );

        return response()->json(['message' => 'Sozlama yangilandi']);
    }

    public function bulkUpdate(UpdateSettingsRequest $request): JsonResponse
    {
        $dtos = array_map(
            fn (array $item) => UpdateSettingDTO::fromArray($item),
            $request->input('settings'),
        );

        $this->updateManyHandler->handle(new UpdateSettingsCommand($dtos));

        return response()->json(['message' => 'Sozlamalar yangilandi']);
    }
}
