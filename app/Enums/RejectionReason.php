<?php

namespace App\Enums;

enum RejectionReason:string
{
    case BlurryImages = 'blurry_images';

    case NationalIdMismatch = 'national_id_mismatch';

    case InvalidDocument = 'invalid_document';

    case FakeDocument = 'fake_document';

    case MissingInformation = 'missing_information';

    case ExpiredDocument = 'expired_document';

    case DuplicateRequest = 'duplicate_request';

    case Other = 'other';
}
