<?php

namespace App\Services\Consignes;

class ConcatenerChampsConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // 1. Destination (ex: nom_prenom)
        $champDestination = $parametres['separateur'] ?? 'nom_prenom';
        $valeursADecomposer = [];

        // 2. Parcourir tous les champs sélectionnés dans tes groupes (Ordre 1, Ordre 2...)
        foreach ($champs as $champ) {
            // Détection flexible de la colonne (gère NOM, nom, Nom...)
            $cleTrouvee = null;
            if (array_key_exists($champ, $ligne)) {
                $cleTrouvee = $champ;
            } elseif (array_key_exists(strtoupper($champ), $ligne)) {
                $cleTrouvee = strtoupper($champ);
            } elseif (array_key_exists(strtolower($champ), $ligne)) {
                $cleTrouvee = strtolower($champ);
            }

            if ($cleTrouvee) {
                $valeur = trim((string)$ligne[$cleTrouvee]);

                // On n'ajoute que si la case n'est pas vide
                if ($valeur !== "" && strtolower($valeur) !== "null") {
                    $valeursADecomposer[] = $valeur;
                }

                // 3. SUPPRESSION de la colonne source (ex: NOM ou PRENOM)
                unset($ligne[$cleTrouvee]);
            }
        }

        // 4. Fusion avec un espace et création de la nouvelle colonne
        if (!empty($valeursADecomposer)) {
            $ligne[$champDestination] = implode(' ', $valeursADecomposer);
        } else {
            $ligne[$champDestination] = '';
        }

        return $ligne;
    }
}
