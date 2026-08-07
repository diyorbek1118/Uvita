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
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'address'                => ['required', 'array'],
            'address.region'         => ['required', 'string'],
            'address.district'       => ['required', 'string'],
            'address.street'         => ['required', 'string'],
            'address.house'          => ['required', 'string'],
            'address.landmark'       => ['nullable', 'string'],
            'address.lat'            => ['nullable', 'numeric', 'between:-90,90'],
            'address.lng'            => ['nullable', 'numeric', 'between:-180,180'],
            'phone'                  => ['required', 'string', 'regex:/^\+998\d{9}$/'],
            'phone_secondary'        => ['nullable', 'string', 'regex:/^\+998\d{9}$/'],
            'delivery_time'          => ['required', 'string'],
            'courier_note'           => ['nullable', 'string', 'max:500'],
            'payment_method'         => ['required', 'string', 'in:payme,click,uzum'],
        ];
    }

    /**
     * Validatsiya o'tgach manzilni geokodlaymiz.
     *
     * - Mijoz address.lat/lng jo'natgan bo'lsa — ularni ishlatamiz va keshlaymiz.
     * - Aks holda Nominatim'ga so'rov (natija address_geocodes'da keshlanadi).
     * - Geokodlash muvaffaqiyatsiz bo'lsa buyurtma baribir yaratiladi (xarita
     *   frontend'da fallback ko'rsatadi).
     */
    protected function passedValidation(): void
    {
        $address = $this->input('address', []);

        if (isset($address['lat'], $address['lng'])
            && is_numeric($address['lat'])
            && is_numeric($address['lng'])) {
            $lat = (float) $address['lat'];
            $lng = (float) $address['lng'];

            $this->merge(['lat' => $lat, 'lng' => $lng, 'geo_level' => 'address']);
            app(AddressGeocoder::class)->remember(self::queryFrom($address), $lat, $lng);

            return;
        }

        try {
            $coords = self::geocodeWithFallback($address);
            if ($coords !== null) {
                $this->merge([
                    'lat'       => $coords['lat'],
                    'lng'       => $coords['lng'],
                    'geo_level' => $coords['level'],
                ]);
            }
        } catch (\Throwable) {
            // Geokodlash xatosi buyurtma yaratishni to'xtatmaydi.
        }
    }

    /**
     * Geokodlash zanjiri: to'liq manzil → hudud.
     * Ikkala darajadagi natijalar ham address_geocodes'da keshlanadi.
     *
     * @return array{lat: float, lng: float, level: 'address'|'region'}|null
     */
    private static function geocodeWithFallback(array $address): ?array
    {
        $geocoder = app(AddressGeocoder::class);

        $query = self::queryFrom($address);
        $coords = $query !== '' ? $geocoder->geocode($query) : null;
        if ($coords !== null) {
            return [...$coords, 'level' => 'address'];
        }

        $region = trim((string) ($address['region'] ?? ''));
        $regionQuery = $region !== '' ? "{$region}, Uzbekistan" : '';
        if ($regionQuery !== '' && $regionQuery !== $query) {
            $coords = $geocoder->geocode($regionQuery);
            if ($coords !== null) {
                return [...$coords, 'level' => 'region'];
            }
        }

        return null;
    }

    private static function queryFrom(array $address): string
    {
        return trim(implode(', ', array_filter([
            $address['region']   ?? '',
            $address['district'] ?? '',
            $address['street']   ?? '',
            $address['house']    ?? '',
            'Uzbekistan',
        ])));
    }
}
