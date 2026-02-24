<?php

namespace App\Services\Consignes;

class DateEmptyConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {

            if (!isset($ligne[$champ]) || trim($ligne[$champ]) === '') {
                $ligne[$champ] = '01/01/1900';
            }
        }

        return $ligne;
    }
}
