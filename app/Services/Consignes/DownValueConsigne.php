<?php

namespace App\Services\Consignes;

class DownValueConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $step = $parametres['step'] ?? 1;

        foreach ($champs as $champ) {
            if (!isset($ligne[$champ])) {
                continue;
            }

            $valeur = trim((string) $ligne[$champ]);

            // ignorer vide ou non numérique
            if ($valeur === '' || !is_numeric($valeur)) {
                continue;
            }

            // décrément
            $ligne[$champ] = $valeur - $step;
        }

        return $ligne;
    }
}
