<?php

namespace App\Enums;

enum ProductStatus: string
{
    //Enum Product Statuses
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
