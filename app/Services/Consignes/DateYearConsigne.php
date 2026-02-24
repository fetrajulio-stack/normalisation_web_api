<?php

namespace App\Services\Consignes;

class DateYearConsigne implements ConsigneInterface
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
                // Conversion en DateTime
                $date = new \DateTime($valeur);

                // Format année YYYY
                $ligne[$champ] = $date->format('Y');

            } catch (\Exception $e) {
                // Si la valeur n'est pas une date valide, on ne modifie pas
            }
        }

        return $ligne;
    }
}
