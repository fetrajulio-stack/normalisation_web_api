<?php

namespace App\Services\Consignes;

class DateStandardConsigne implements ConsigneInterface
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

            // Essayer de convertir en DateTime
            try {
                // Création automatique de la date
                $date = new \DateTime($valeur);
                // Format jj/mm/aaaa
                $ligne[$champ] = $date->format('d/m/Y');
            } catch (\Exception $e) {
                // Si échec, on laisse la valeur inchangée
            }
        }

        return $ligne;
    }
}
