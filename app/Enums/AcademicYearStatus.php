<?php declare(strict_types=1);

namespace App\Enums;

enum AcademicYearStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';
}
