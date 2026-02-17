<?php

namespace App\Services\Consignes;

class DateDayMonthConsigne implements ConsigneInterface
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

            try {
                // Tentative de conversion en DateTime
                $date = new \DateTime($valeur);

                // Format JJ/MM
                $ligne[$champ] = $date->format('d/m');

            } catch (\Exception $e) {
                // Si la valeur n'est pas une date valide, on ne modifie pas
            }
        }

        return $ligne;
    }
}
