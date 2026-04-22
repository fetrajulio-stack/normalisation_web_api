<?php

namespace App\Services\Consignes;

class ConcatenerChampsConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $champDestination = $parametres['separateur'] ?? 'nom_prenom';

        $valeursADecomposer = [];
        $clesASupprimer = [];
        $positionInsertion = null;

        $keys = array_keys($ligne);

        // 🔹 1. Récupérer les valeurs + position
        foreach ($champs as $champ) {

            foreach ($keys as $index => $key) {

                if (strtolower($key) === strtolower($champ)) {

                    // 📌 position du premier champ (nom)
                    if ($positionInsertion === null) {
                        $positionInsertion = $index;
                    }

                    $valeur = trim((string)$ligne[$key]);

                    if ($valeur !== "" && strtolower($valeur) !== "null") {
                        $valeursADecomposer[] = $valeur;
                    }

                    $clesASupprimer[] = $key;
                }
            }
        }

        // 🔹 2. Supprimer les champs source
        foreach ($clesASupprimer as $cle) {
            unset($ligne[$cle]);
        }

        // 🔹 3. Construire valeur fusionnée
        $valeurFusionnee = !empty($valeursADecomposer)
            ? implode(' ', $valeursADecomposer)
            : '';

        // 🔹 4. Si aucune position trouvée → fallback
        if ($positionInsertion === null) {
            $ligne[$champDestination] = $valeurFusionnee;
            return $ligne;
        }

        // 🔹 5. Insérer à la bonne position
        $resultat = [];
        $i = 0;

        foreach ($ligne as $key => $value) {
            if ($i === $positionInsertion) {
                $resultat[$champDestination] = $valeurFusionnee;
            }
            $resultat[$key] = $value;
            $i++;
        }

        // 🔹 6. Sécurité (si fin)
        if ($positionInsertion >= count($ligne)) {
            $resultat[$champDestination] = $valeurFusionnee;
        }

        return $resultat;
    }
}