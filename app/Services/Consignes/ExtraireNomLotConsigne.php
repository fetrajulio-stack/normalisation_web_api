<?php

namespace App\Services\Consignes;

class ExtraireNomLotConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $source = 'n_lot'; // Source fixe

        // PRIORITÉ :
        // 1. Un champ cible défini dans les paramètres
        // 2. Le premier champ coché dans le groupe (ex: nom_association)
        $cible = $parametres['champ_cible'] ?? ($champs[0] ?? null);

        // Paramètres dynamiques
        $separateur = $parametres['separateur'] ?? '_';
        $position = isset($parametres['position']) ? (int)$parametres['position'] : 0;

        if (!$cible || !isset($ligne[$source])) {
            return $ligne;
        }

        $valeurSource = trim((string)$ligne[$source]);

        if ($valeurSource === "") {
            return $ligne;
        }

        // Découpage dynamique
        $parties = explode($separateur, $valeurSource);

        // Assignation à la cible (ex: nom_association prend la valeur extraite)
        $ligne[$cible] = isset($parties[$position]) ? trim($parties[$position]) : '';

        return $ligne;
    }
}
