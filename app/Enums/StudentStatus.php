<?php

namespace App\Enums;

enum StudentStatus: string {
    case DRAFT = 'Draft';
    case REGISTERED = 'Registered';
    case ADMITTED = 'Admitted';
    case PENDING = 'Pending';
    case REJECTED = 'Rejected';
    case GRADUATED = 'Graduated';
    case SUSPENDED = 'Suspended';
}
