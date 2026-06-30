<?php

namespace App\Enums;

enum WhatsAppGateway: string
{
    case Transactional = 'transactional';
    case Bulk = 'bulk';
}
