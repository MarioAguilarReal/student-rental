<?php

namespace App\Modules\Payments\Enums;

enum EntryDirection: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
