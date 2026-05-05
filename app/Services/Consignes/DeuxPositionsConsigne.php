<?php

namespace App\Services\Consignes;

class DeuxPositionsConsigne implements ConsigneInterface
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

            // vérifier si numérique entier
            if (ctype_digit($valeur)) {
                // format sur 2 positions (ex: 1 -> 01)
                $ligne[$champ] = str_pad($valeur, 2, '0', STR_PAD_LEFT);
                continue;
            }

            // si numérique décimal (optionnel)
            if (is_numeric($valeur)) {
                // on garde tel quel (ou tu peux adapter selon besoin)
                $ligne[$champ] = $valeur;
            }
        }

        return $ligne;
    }
}