<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'model_variant_id' => ['required', 'exists:model_variants,id'],
            'order_prefix' => ['required', 'integer', 'min:1000', 'max:9999'],
            'website' => ['present', 'max:0'], // honeypot — must be empty
            '_ts' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.max' => 'Invalid submission.',
            '_ts.required' => 'Invalid submission.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->validateTimestamp()) {
                $validator->errors()->add('_ts', 'Invalid submission. Please try again.');
            }
        });
    }

    private function validateTimestamp(): bool
    {
        $ts = $this->input('_ts', '');
        $parts = explode('.', $ts, 2);

        if (count($parts) !== 2) {
            return false;
        }

        [$timestamp, $hmac] = $parts;

        // Verify HMAC
        $expected = hash_hmac('sha256', $timestamp, config('app.key'));
        if (!hash_equals($expected, $hmac)) {
            return false;
        }

        // Check elapsed time: must be between 3 seconds and 1 hour
        $elapsed = time() - (int) $timestamp;
        if ($elapsed < 3 || $elapsed > 3600) {
            return false;
        }

        return true;
    }
}
