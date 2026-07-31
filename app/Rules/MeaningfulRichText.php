<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MeaningfulRichText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $text = trim(html_entity_decode(strip_tags($this->toText($value)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', '', $text) ?? '';

        if ($text === '') {
            $fail('Isi berita tidak boleh kosong.');
        }
    }

    private function toText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value['content'] ?? [])
                ->map(fn (mixed $child): string => $this->toText($child))
                ->implode(' ').' '.($value['text'] ?? '');
        }

        if (is_object($value)) {
            return $this->toText((array) $value);
        }

        return '';
    }
}
