<?php

namespace App\Enums;

enum StatusPengerjaan: string
{
    case Pending = 'pending';
    case Ongoing = 'ongoing';
    case Done = 'done';
    case Cancel = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Ongoing => 'On-going',
            self::Done => 'Done',
            self::Cancel => 'Cancel',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Ongoing => 'blue',
            self::Done => 'green',
            self::Cancel => 'red',
        };
    }
}
