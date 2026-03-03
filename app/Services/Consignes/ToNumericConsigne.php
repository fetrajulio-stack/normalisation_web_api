<?php

namespace App\Services\Consignes;

class ToNumericConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {
            if (!isset($ligne[$champ])) {
                continue;
            }

            $valeur = trim((string) $ligne[$champ]);

            // ignorer vide
            if ($valeur === '') {
                continue;
            }

            // numérique entier
            if (ctype_digit($valeur)) {
                $ligne[$champ] = (int) $valeur;
                continue;
            }

            // numérique décimal (optionnel)
            if (is_numeric($valeur)) {
                $ligne[$champ] = $valeur + 0; // cast int ou float automatiquement
            }
        }

        return $ligne;
    }
}
