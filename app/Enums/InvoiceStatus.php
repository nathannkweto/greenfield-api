<?php

namespace App\Enums;

enum InvoiceStatus: string {
    case PENDING = 'Pending';
    case PARTIALLY_PAID = 'Partially Paid';
    case PAID = 'Paid';
    case VOID = 'Void';
}