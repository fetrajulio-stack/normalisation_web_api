<?php

namespace App\Services\Consignes;

class SiAutreChampRenseigneConsigne
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        //dump($ligne);
        $champSource = $parametres['champ_source'] ?? null;
        $valeurCible = $parametres['valeur_cible'] ?? null;

        if (!$champSource || $valeurCible === null) {
            throw new \Exception("Paramètres manquants pour SI_AUTRE_CHAMP_RENSEIGNE");
        }

        $champSource = strtolower($champSource);

        if (
            isset($ligne[$champSource]) &&
            trim($ligne[$champSource]) !== ''
        ) {
            foreach ($champs as $champ) {
                $ligne[$champ] = $valeurCible;
            }
        }
        //dd($ligne);
        return $ligne;
    }
}
