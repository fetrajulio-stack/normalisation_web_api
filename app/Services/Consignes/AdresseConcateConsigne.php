<?php

namespace App\Services\Consignes;

class AdresseConcateConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $valeurs = [];

        foreach ($champs as $champ) {

            if (!isset($ligne[$champ])) {
                continue;
            }

            $valeur = trim($ligne[$champ]);

            if ($valeur !== '') {
                $valeurs[] = $valeur;
            }
        }

        // Concaténation avec espace
        $adresseComplete = implode(' ', $valeurs);

        // Définir le champ cible
        $champCible = $parametres['champ_cible'] ?? $champs[0];

        $ligne[$champCible] = $adresseComplete;

        return $ligne;
    }
}
