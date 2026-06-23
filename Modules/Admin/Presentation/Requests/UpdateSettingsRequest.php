<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Admin\Domain\ValueObjects\SettingKey;

class UpdateSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        $keys = array_column(SettingKey::cases(), 'value');

        return [
            'settings'         => ['required', 'array', 'min:1'],
            'settings.*.key'   => ['required', 'string', 'in:' . implode(',', $keys)],
            'settings.*.value' => ['required', 'string'],
        ];
    }
}
