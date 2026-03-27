<?php

namespace App\Services\Consignes;

class EmptyAlwaysConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {

            if (!array_key_exists($champ, $ligne)) {
                continue;
            }

            // Forcer à vide
            $ligne[$champ] = '';
        }

        return $ligne;
    }
}