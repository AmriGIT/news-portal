<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('import_token');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package' => [
                'required',
                'file',
                'mimes:zip',
                'max:'.((int) config('news-import.max_zip_mb', 50) * 1024),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $handle = @fopen($value->getRealPath(), 'rb');
                    $signature = $handle ? fread($handle, 4) : false;

                    if (is_resource($handle)) {
                        fclose($handle);
                    }

                    if ($signature !== "PK\x03\x04" && $signature !== "PK\x05\x06" && $signature !== "PK\x07\x08") {
                        $fail('File package harus berupa ZIP valid.');
                    }
                },
            ],
            'publish_mode' => ['nullable', Rule::in(['draft', 'published'])],
        ];
    }

    public function publishMode(): string
    {
        return (string) ($this->validated('publish_mode') ?: 'draft');
    }
}
