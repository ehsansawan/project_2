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

    case FirstNameMismatch = 'first_name_mismatch';

    case LastNameMismatch = 'last_name_mismatch';

    case DateOfBirthMismatch = 'date_of_birth_mismatch';

    case Other = 'other';
}
