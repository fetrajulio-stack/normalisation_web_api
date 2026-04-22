<?php

namespace App\Services\Consignes;

class SiAutreChampRenseigneConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $cible = $parametres['champ_principal'] ?? null;
        $source = $parametres['champ_autre'] ?? null;

        $rawDeclic = $parametres['valeur_declencheuse'] ?? "";
        $declic = trim((string)$rawDeclic);

        if (!$cible || !$source) {
            return $ligne;
        }

        $cleCible = null;
        $cleSource = null;

        // 🔹 Détection ULTRA robuste (gère -, _, casse)
        foreach ($ligne as $key => $value) {

            $keyNorm = strtolower(str_replace(['-', '_'], '', $key));
            $sourceNorm = strtolower(str_replace(['-', '_'], '', $source));
            $cibleNorm  = strtolower(str_replace(['-', '_'], '', $cible));

            if ($keyNorm === $cibleNorm) {
                $cleCible = $key;
            }

            if ($keyNorm === $sourceNorm) {
                $cleSource = $key;
            }
        }

        // Si source introuvable → rien à faire
        if (!$cleSource) {
            return $ligne;
        }

        $valeurSource = trim((string)($ligne[$cleSource] ?? ""));
        $valeurCible  = trim((string)($ligne[$cleCible] ?? ""));

        // 🔹 Transfert
        if ($valeurSource !== "") {
            if ($declic === "" || $valeurCible === $declic) {
                $ligne[$cleCible] = $valeurSource;
            }
        }

        // 🔥 SUPPRESSION FORCÉE (toutes variantes possibles)
        foreach ($ligne as $key => $v) {
            $keyNorm = strtolower(str_replace(['-', '_'], '', $key));
            $sourceNorm = strtolower(str_replace(['-', '_'], '', $source));

            if ($keyNorm === $sourceNorm) {
                unset($ligne[$key]);
            }
        }

        return $ligne;
    }
}