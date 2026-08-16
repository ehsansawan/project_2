<?php

namespace App\Enums;

enum ProjectStatus :string
{
    case Planning = 'planning';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Open = 'open';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}