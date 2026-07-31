<?php

namespace App\Support;

class LikePatternEscaper
{
    public function escape(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}
