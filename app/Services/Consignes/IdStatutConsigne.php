<?php

namespace App\Services\Consignes;

class IdStatutConsigne implements ConsigneInterface
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
                    $ligne[$champ] = 'Salarié(e)';
                    break;

                case '2':
                    $ligne[$champ] = 'Retraité(e)';
                    break;

                case '3':
                    $ligne[$champ] = 'Autre';
                    break;

                default:
                    // on ne touche pas si le code n'est pas prévu
                    break;
            }
        }

        return $ligne;
    }
}
