<?php

namespace App\Enums;

enum AuditAction :string
{
    //
    case Reject = "reject";
    case Approve = "approve";
    case Delete = "delete";
}
