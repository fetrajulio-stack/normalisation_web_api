<?php

namespace App\Services\Consignes;

class DateYearMonthDayConsigne implements ConsigneInterface
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
                // Création de la date depuis n'importe quel format valide
                $date = new \DateTime($valeur);
                // Format AAAA/JJ/MM
                $ligne[$champ] = $date->format('Y/d/m');
            } catch (\Exception $e) {
                // En cas d'erreur, on laisse la valeur inchangée
            }
        }

        return $ligne;
    }
}
