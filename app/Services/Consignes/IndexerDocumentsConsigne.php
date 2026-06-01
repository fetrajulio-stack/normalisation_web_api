<?php

namespace App\Services\Consignes;

class IndexerDocumentsConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        return $ligne;
    }
}