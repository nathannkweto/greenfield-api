<?php

namespace App\Enums;

enum EnrollmentStatus: string {
    case IN_PROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
    case DROPPED = 'Dropped';
}