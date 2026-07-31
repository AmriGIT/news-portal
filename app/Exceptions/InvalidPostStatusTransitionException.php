<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidPostStatusTransitionException extends RuntimeException
{
    public static function invalidTransition(): self
    {
        return new self('Perubahan status berita tidak valid.');
    }

    public static function missingPublicationRequirements(): self
    {
        return new self('Berita belum memenuhi syarat untuk diterbitkan.');
    }

    public static function scheduleMustBeInFuture(): self
    {
        return new self('Waktu publikasi harus berada di masa depan.');
    }
}
