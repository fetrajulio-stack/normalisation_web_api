<?php

namespace App\Services\Consignes;

class PrefixAfaConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {
            if (!isset($ligne[$champ])) {
                continue;
            }

            $valeur = trim((string) $ligne[$champ]);

            if ($valeur === '') {
                continue;
            }

            // Ajouter le préfixe devant la valeur
            $ligne[$champ] = 'Association France Alzheimer ' . $valeur;
        }

        return $ligne;
    }
}
