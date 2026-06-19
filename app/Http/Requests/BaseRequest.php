<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    /**
     * Har doim true — ruxsat tekshiruvi middleware da hal qilinadi.
     */
    public function authorize(): bool
    {
        return true;
    }
}