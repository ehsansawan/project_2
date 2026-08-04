<?php

namespace App\Enums;

enum SkillType: string
{
    case Technical = 'technical';
    case Craftsmanship = 'craftsmanship';
    case Construction = 'construction';
    case Social = 'social';
    case Educational = 'educational';
    case Medical = 'medical';
    case Driving = 'driving';
    case Logistics = 'logistics';
    case Environmental = 'environmental';
    case Other = 'other';
}
