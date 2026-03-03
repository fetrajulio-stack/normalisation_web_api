<?php

namespace App\Services\Consignes;

class DateSectionConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {

            if (!isset($ligne[$champ])) {
                continue;
            }

            $valeur = trim($ligne[$champ]);

            if ($valeur === '§') {
                $ligne[$champ] = '01/01/1900';
            }
        }

        return $ligne;
    }
}
