<?php

declare(strict_types=1);

namespace Modules\Order\Presentation\Requests;

use App\Shared\Services\Geo\AddressGeocoder;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'address' => ['required', 'array'],
            'address.region' => ['required', 'string'],
            'address.district' => ['required', 'string'],
            'address.street' => ['required', 'string'],
            'address.house' => ['required', 'string'],
            'address.landmark' => ['nullable', 'string'],
            'address.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'address.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_latitude' => ['nullable', 'required_with:delivery_longitude', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'required_with:delivery_latitude', 'numeric', 'between:-180,180'],
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/'],
            'phone_secondary' => ['nullable', 'string', 'regex:/^\+998\d{9}$/'],
            'delivery_time' => ['required', 'string'],
            'courier_note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:cash,payme,click,uzum'],
            'delivery_scope' => ['nullable', 'string', 'in:city,district_center'],
        ];
    }

    protected function passedValidation(): void
    {
        $address = $this->input('address', []);
        $lat = $address['lat'] ?? $this->input('delivery_latitude');
        $lng = $address['lng'] ?? $this->input('delivery_longitude');

        if (is_numeric($lat) && is_numeric($lng)) {
            $coordinates = ['lat' => (float) $lat, 'lng' => (float) $lng];
            $this->merge([
                ...$coordinates,
                'geo_level' => 'address',
                'delivery_latitude' => $coordinates['lat'],
                'delivery_longitude' => $coordinates['lng'],
            ]);
            app(AddressGeocoder::class)->remember(self::queryFrom($address), ...$coordinates);

            return;
        }

        try {
            $coordinates = self::geocodeWithFallback($address);
            if ($coordinates !== null) {
                $this->merge([
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng'],
                    'geo_level' => $coordinates['level'],
                    'delivery_latitude' => $coordinates['lat'],
                    'delivery_longitude' => $coordinates['lng'],
                ]);
            }
        } catch (\Throwable) {
            // Geokodlash xatosi buyurtma yaratishni to'xtatmaydi.
        }
    }

    /** @return array{lat: float, lng: float, level: 'address'|'region'}|null */
    private static function geocodeWithFallback(array $address): ?array
    {
        $geocoder = app(AddressGeocoder::class);
        $query = self::queryFrom($address);
        $coordinates = $query !== '' ? $geocoder->geocode($query) : null;
        if ($coordinates !== null) {
            return [...$coordinates, 'level' => 'address'];
        }

        $region = trim((string) ($address['region'] ?? ''));
        $regionQuery = $region !== '' ? "{$region}, Uzbekistan" : '';
        if ($regionQuery !== '' && $regionQuery !== $query) {
            $coordinates = $geocoder->geocode($regionQuery);
            if ($coordinates !== null) {
                return [...$coordinates, 'level' => 'region'];
            }
        }

        return null;
    }

    private static function queryFrom(array $address): string
    {
        return trim(implode(', ', array_filter([
            $address['region'] ?? '',
            $address['district'] ?? '',
            $address['street'] ?? '',
            $address['house'] ?? '',
            'Uzbekistan',
        ])));
    }
}
