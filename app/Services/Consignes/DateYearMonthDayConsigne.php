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

            // Normaliser les dates numériques DDMMYYYY ou YYYYMMDD
            if (preg_match('/^\d{8}$/', $valeur)) {
                if (substr($valeur, 0, 2) > 31) {
                    // Format probable YYYYMMDD
                    $année = substr($valeur, 0, 4);
                    $mois  = substr($valeur, 4, 2);
                    $jour  = substr($valeur, 6, 2);
                } else {
                    // Format probable DDMMYYYY
                    $jour  = substr($valeur, 0, 2);
                    $mois  = substr($valeur, 2, 2);
                    $année = substr($valeur, 4, 4);
                }
                $valeur = "$année-$mois-$jour";
            }

            try {
                $date = new \DateTime($valeur);
                // ✅ Format AAAA/MM/JJ
                $ligne[$champ] = $date->format('Y/m/d');
            } catch (\Exception $e) {
                // On laisse la valeur inchangée si erreur
            }
        }

        return $ligne;
    }
}