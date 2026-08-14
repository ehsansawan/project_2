<?php

namespace App\Enums;

enum CertificateRejectionReason: string
{
    case FakeDocument = 'fake_document';            // مزورة
    case ExpiredDocument = 'expired_document';      // منتهية الصلاحية
    case InvalidDocument = 'invalid_document';      // غير صالحة/غير قابلة للتحقق
    case BlurryImage = 'blurry_image';              // صورة ضبابية
    case NameMismatch = 'name_mismatch';            // الاسم لا يطابق الهوية/المهارة
    case IssuerUnverifiable = 'issuer_unverifiable';// الجهة المصدرة غير معروفة
    case MissingInformation = 'missing_information';// معلومات ناقصة
    case Other = 'other';
}
