<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Admin\Domain\ValueObjects\SettingKey;

class UpdateSettingRequest extends FormRequest
{
    public function rules(): array
    {
        $keys = array_column(SettingKey::cases(), 'value');

        return [
            'key'   => ['required', 'string', 'in:' . implode(',', $keys)],
            'value' => ['required', 'string'],
        ];
    }
}
