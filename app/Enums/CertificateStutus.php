<?php

namespace App\Enums;

enum CertificateStutus:string
{
    //
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
