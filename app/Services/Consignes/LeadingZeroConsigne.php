<?php

namespace App\Services\Consignes;

class LeadingZeroConsigne implements ConsigneInterface
{
    /**
     * @param array $ligne
     * @param array $champs
     * @param array $parametres [
     *     'length' => int  // nombre total de chiffres (défaut: 2)
     * ]
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $length = $parametres['length'] ?? 2;

        foreach ($champs as $champ) {
            if (!isset($ligne[$champ])) {
                continue;
            }

            $valeur = trim((string) $ligne[$champ]);

            // ignorer vide ou non numérique
            if ($valeur === '' || !ctype_digit($valeur)) {
                continue;
            }

            $ligne[$champ] = str_pad($valeur, $length, '0', STR_PAD_LEFT);
        }

        return $ligne;
    }
}
