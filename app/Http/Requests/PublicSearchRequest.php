<?php

namespace App\Http\Requests;

use App\Services\PostSearchService;
use Illuminate\Foundation\Http\FormRequest;

class PublicSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.min' => 'Kata kunci minimal 2 karakter.',
            'q.max' => 'Kata kunci maksimal 100 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $keyword = app(PostSearchService::class)->normalize((string) $this->query('q', ''));

        $this->merge([
            'q' => $keyword === '' ? null : $keyword,
        ]);
    }

    public function keyword(): string
    {
        return app(PostSearchService::class)->normalize((string) $this->validated('q', ''));
    }
}
