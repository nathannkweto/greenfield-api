<?php declare(strict_types=1);

namespace App\Enums;

enum CalendarEventCategory: string
{
    case HOLIDAY = 'HOLIDAY';
    case EXAM = 'EXAM';
    case REGISTRATION = 'REGISTRATION';
    case ACADEMIC = 'ACADEMIC';
    case EVENT = 'EVENT';
}
