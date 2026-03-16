<?php

namespace App\Services\Consignes;

class DownValueConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $step = $parametres['step'] ?? 1;

        foreach ($champs as $champ) {

            if (!array_key_exists($champ, $ligne)) {
                continue;
            }

            $valeur = $ligne[$champ];

            // ignorer vide ou non numérique
            if ($valeur === null || $valeur === '' || !is_numeric($valeur)) {
                continue;
            }

            // décrément
            $ligne[$champ] = (int)$valeur - (int)$step;
        }

        return $ligne;
    }
}