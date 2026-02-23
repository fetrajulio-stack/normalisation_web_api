<?php
namespace App\Services\Consignes;

class CompactageConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {

// 1️⃣ Récupérer uniquement les valeurs non vides
        $valeurs = [];

        foreach ($champs as $champ) {
            if (
                array_key_exists($champ, $ligne) &&
                $ligne[$champ] !== null &&
                $ligne[$champ] !== ''
            ) {
                $valeurs[] = $ligne[$champ];
            }
        }

        // 2️⃣ Mettre tous les champs du groupe à null
        foreach ($champs as $champ) {
            $ligne[$champ] = null;
        }

        // 3️⃣ Réinjecter les valeurs vers l'avant
        foreach ($champs as $index => $champ) {
            if (isset($valeurs[$index])) {
                $ligne[$champ] = $valeurs[$index];
            }
        }

        return $ligne;
    }
}

