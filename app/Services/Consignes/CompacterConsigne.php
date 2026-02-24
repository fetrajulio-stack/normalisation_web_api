<?php

namespace App\Services\Consignes;

class CompacterConsigne implements ConsigneInterface
{
    /**
     * @param array $ligne
     * @param array $champs      Champs à compacter (ex: Q35_1 à Q35_20)
     * @param array $parametres  [
     *     'cible' => string,    // champ de destination
     *     'separateur' => string,
     *     'ignorer_zero' => bool
     * ]
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // 1. Forcer l'ordre naturel des champs (q8_1, q8_2, q8_3...)
        natsort($champs);
        $champs = array_values($champs);

        // 2. Récupérer les valeurs non vides
        $valeurs = [];

        foreach ($champs as $champ) {
            if (!array_key_exists($champ, $ligne)) {
                continue;
            }
            $valeur = trim((string) $ligne[$champ]);

            if ($valeur !== '') {
                $valeurs[] = $valeur;
            }
        }
        // 3. Réinjecter en compactant vers le début
        foreach ($champs as $index => $champ) {
            $ligne[$champ] = $valeurs[$index] ?? '';
        }
        return $ligne;
    }
}
