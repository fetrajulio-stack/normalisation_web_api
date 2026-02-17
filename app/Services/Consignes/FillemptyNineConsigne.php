<?php

namespace App\Services\Consignes;

class FillemptyNineConsigne
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $valeurParDefaut = $parametres['valeur'] ?? '9';

        foreach ($champs as $champ) {
            if (
                !array_key_exists($champ, $ligne) ||
                $ligne[$champ] === null ||
                trim($ligne[$champ]) === ''
            ) {
                $ligne[$champ] = $valeurParDefaut;
            }
        }
        return $ligne;
    }
}

