<?php

namespace App\Enums;

enum PaymentStatus: string {
    case COMPLETED = 'Completed';
    case PENDING = 'Pending';
    case FAILED = 'Failed';
}
