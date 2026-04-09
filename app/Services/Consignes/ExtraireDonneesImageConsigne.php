<?php

namespace App\Services\Consignes;

class ExtraireDonneesImageConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // Source fixe : n_ima (nom de l'image)
        $source = 'n_ima';

        // La cible est le premier champ coché dans le groupe (ex: num_page)
        $cible = $champs[0] ?? null;

        $separateur = $parametres['separateur'] ?? '_';
        $position = isset($parametres['position']) ? (int)$parametres['position'] : 0;

        if (!$cible || !isset($ligne[$source])) {
            return $ligne;
        }

        $valeurSource = trim((string)$ligne[$source]);

        if ($valeurSource === "") {
            return $ligne;
        }

        // Nettoyage de l'extension (optionnel, ex: retirer .TIF)
        $valeurNettoyee = pathinfo($valeurSource, PATHINFO_FILENAME);

        // Découpage
        $parties = explode($separateur, $valeurNettoyee);

        // Assignation
        $ligne[$cible] = isset($parties[$position]) ? trim($parties[$position]) : '';

        return $ligne;
    }
}
