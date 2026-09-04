<?php

namespace App\Enums;

enum PaymentMethod: string {
    case CREDIT_CARD = 'Credit Card';
    case BANK_TRANSFER = 'Bank Transfer';
    case CASH = 'Cash';
    case SCHOLARSHIP = 'Scholarship';
}