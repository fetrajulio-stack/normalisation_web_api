<?php

namespace App\Services\Consignes;

class IdSocialConsigne implements ConsigneInterface
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

            switch ($valeur) {
                case '1':
                    $ligne[$champ] = 'Une femme';
                    break;

                case '2':
                    $ligne[$champ] = 'Un homme';
                    break;

                default:
                    // on ne touche pas
                    break;
            }
        }

        return $ligne;
    }
}
