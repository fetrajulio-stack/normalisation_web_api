<?php

namespace App\Services\Consignes;

class UppercaseConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {

        foreach ($champs as $champ) {
            if (isset($ligne[$champ]) && $ligne[$champ] !== null) {
                $ligne[$champ] = strtoupper($ligne[$champ]);
            }
        }
        return $ligne;
    }
}
