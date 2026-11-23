<?php

namespace App\Enums;

enum TransactionSource: string
{
    case Web = 'web';
    case Api = 'api';
    case Whatsapp = 'whatsapp';
    case N8n = 'n8n';
    case Import = 'import';
    case System = 'system';
}
