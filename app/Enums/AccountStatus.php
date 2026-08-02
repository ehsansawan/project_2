<?php

namespace App\Enums;

enum AccountStatus :string
{
    //
    case Visitor = 'visitor';
    case Verified = 'verified';
    case Closed = 'closed';
}
