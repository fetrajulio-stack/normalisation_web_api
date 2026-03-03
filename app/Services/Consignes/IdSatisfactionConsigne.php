<?php

namespace App\Services\Consignes;

class IdSatisfactionConsigne implements ConsigneInterface
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
                    $ligne[$champ] = 'Oui tout à fait';
                    break;

                case '2':
                    $ligne[$champ] = 'Oui plutôt';
                    break;

                case '3':
                    $ligne[$champ] = 'Non plutôt pas';
                    break;

                case '4':
                    $ligne[$champ] = 'Non pas du tout';
                    break;

                default:
                    // on ne touche pas si le code n'est pas prévu
                    break;
            }
        }

        return $ligne;
    }
}
