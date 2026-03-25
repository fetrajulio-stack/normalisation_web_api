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

            // Normaliser les dates numériques (DDMMYYYY ou YYYYMMDD)
            if (preg_match('/^\d{8}$/', $valeur)) {
                if (substr($valeur, 0, 2) > 31) {
                    // YYYYMMDD
                    $annee = substr($valeur, 0, 4);
                    $mois  = substr($valeur, 4, 2);
                    $jour  = substr($valeur, 6, 2);
                } else {
                    // DDMMYYYY
                    $jour  = substr($valeur, 0, 2);
                    $mois  = substr($valeur, 2, 2);
                    $annee = substr($valeur, 4, 4);
                }

                $valeur = "$annee-$mois-$jour"; // format ISO
            }

            try {
                $date = new \DateTime($valeur);
                // ✅ Format jj/mm/aaaa
                $ligne[$champ] = $date->format('d/m/Y');
            } catch (\Exception $e) {
                // On garde la valeur si erreur
            }
        }

        return $ligne;
    }
}