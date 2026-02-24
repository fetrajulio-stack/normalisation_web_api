<?php

namespace App\Services\Consignes;

class PrefixConsigne
{
    /**
     * Applique la consigne de préfixe sur un champ.
     *
     * @param array $ligne La ligne de données
     * @param array $champs Les champs à traiter
     * @param array $parametres Doit contenir 'prefixe'
     * @return array La ligne modifiée
     *
     * @throws \Exception
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $prefixe = $parametres['prefixe'] ?? null;

        if (!$prefixe) {
            throw new \Exception("Paramètre 'prefixe' manquant pour la consigne PREFIXE");
        }

        foreach ($champs as $champ) {
            $champ = strtolower($champ);

            if (!isset($ligne[$champ])) {
                continue; // Champ inexistant, on ignore
            }

            if (!empty($ligne[$champ])) {
                // Ajouter le préfixe devant la valeur existante
                $ligne[$champ] = $prefixe . ' ' . $ligne[$champ];
            }
        }
         //   dd($ligne);
        return $ligne;
    }
}
