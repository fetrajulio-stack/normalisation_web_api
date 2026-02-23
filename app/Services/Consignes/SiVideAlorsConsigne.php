<?php

namespace App\Services\Consignes;

class SiVideAlorsConsigne
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $valeurDefaut = $parametres['valeur_defaut'] ?? null;

        if ($valeurDefaut === null) {
            throw new \Exception("Paramètre 'valeur_defaut' manquant pour la consigne SI_VIDE_ALORS");
        }

        foreach ($champs as $champ) {

            if (
                !array_key_exists($champ, $ligne) ||
                $ligne[$champ] === null ||
                trim($ligne[$champ]) === ''
            ) {
                $ligne[$champ] = $valeurDefaut;
            }
        }

        return $ligne;
    }
}
