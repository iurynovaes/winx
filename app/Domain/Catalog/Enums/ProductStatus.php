<?php

namespace App\Domain\Catalog\Enums;

enum ProductStatus: string
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';
}
