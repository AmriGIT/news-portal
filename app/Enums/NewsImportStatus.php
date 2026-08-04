<?php

namespace App\Enums;

enum NewsImportStatus: string
{
    case Uploaded = 'uploaded';
    case Validating = 'validating';
    case Ready = 'ready';
    case Importing = 'importing';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Terunggah',
            self::Validating => 'Validasi',
            self::Ready => 'Siap',
            self::Importing => 'Import',
            self::Completed => 'Selesai',
            self::CompletedWithErrors => 'Selesai dengan Error',
            self::Failed => 'Gagal',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
