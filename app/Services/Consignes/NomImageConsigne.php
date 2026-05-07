<?php

namespace App\Services\Consignes;

class NomImageConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // Source fixe : nom image
        $source = 'n_ima';

        // Champ cible sélectionné
        $cible = $parametres['champ_cible'] ?? ($champs[0] ?? null);

        // Vérifications
        if (!$cible || !isset($ligne[$source])) {
            return $ligne;
        }

        // Récupération du nom image
        $valeurSource = trim((string)$ligne[$source]);

        // Si vide → on ne fait rien
        if ($valeurSource === '') {
            return $ligne;
        }

        // Copie complète du nom image
        $ligne[$cible] = $valeurSource;

        return $ligne;
    }
}