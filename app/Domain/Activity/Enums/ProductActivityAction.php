<?php

namespace App\Domain\Activity\Enums;

enum ProductActivityAction: string
{
    case Criado = 'criado';
    case Atualizado = 'atualizado';
    case Excluido = 'excluido';
}
