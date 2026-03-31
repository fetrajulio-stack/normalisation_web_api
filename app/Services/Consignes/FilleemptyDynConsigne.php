<?php

namespace App\Services\Consignes;

class FillemptyDynConsigne
{
    /**
     * Remplit les champs vides avec une valeur personnalisée (Texte ou Chiffre).
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // On récupère 'valeur_defaut' qui vient du JSON envoyé par le React
        $valeurSaisie = $parametres['valeur_defaut'] ?? null;

        foreach ($champs as $champ) {
            // Si le champ est absent, null ou juste des espaces vides
            if (
                !array_key_exists($champ, $ligne) ||
                $ligne[$champ] === null ||
                trim((string)$ligne[$champ]) === ''
            ) {
                // On injecte la valeur (peut être "9", "NR", "0", etc.)
                $ligne[$champ] = $valeurSaisie;
            }
        }

        return $ligne;
    }
}
