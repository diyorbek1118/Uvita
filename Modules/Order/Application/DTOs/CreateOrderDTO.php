<?php

declare(strict_types=1);

namespace Modules\Order\Application\DTOs;

use Modules\Order\Presentation\Requests\CreateOrderRequest;

final readonly class CreateOrderDTO
{
    public function __construct(
        public int     $userId,
        public array   $items,
        public array   $address,
        public string  $phone,
        public ?string $phoneSecondary,
        public string  $deliveryTime,
        public ?string $courierNote,
        public string  $paymentMethod,
        public ?float  $lat = null,
        public ?float  $lng = null,
        public ?string $geoLevel = null,
    ) {}

    public static function fromRequest(CreateOrderRequest $request, int $userId): static
    {
        return new static(
            userId:         $userId,
            items:          $request->input('items'),
            address:        $request->input('address'),
            phone:          $request->input('phone'),
            phoneSecondary: $request->input('phone_secondary'),
            deliveryTime:   $request->input('delivery_time'),
            courierNote:    $request->input('courier_note'),
            paymentMethod:  $request->input('payment_method'),
            lat:            is_numeric($request->input('lat')) ? (float) $request->input('lat') : null,
            lng:            is_numeric($request->input('lng')) ? (float) $request->input('lng') : null,
            geoLevel:       $request->input('geo_level') ?: null,
        );
    }
}
