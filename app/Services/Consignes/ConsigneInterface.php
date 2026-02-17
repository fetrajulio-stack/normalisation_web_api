<?php
namespace App\Services\Consignes;

interface ConsigneInterface
{
    public function appliquer(
        array $ligne,
        array $champs,
        array $parametres = []
    ): array;
}

