<?php

namespace App\Services\Consignes;

class SiAutreChampRenseigneConsigne implements ConsigneInterface
{
    /**
     * Applique la logique de substitution et suppression
     * Test 3 : Déclencheur vide -> Substitution systématique si source pleine
     * Tests 1, 2, 4 : Déclencheur rempli -> Substitution si Cible == Déclencheur
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $cible = $parametres['champ_principal'] ?? null;
        $source = $parametres['champ_autre'] ?? null;

        // Sécurité sur le déclencheur pour éviter le "1" résiduel
        $rawDeclic = $parametres['valeur_declencheuse'] ?? "";
        if ($rawDeclic === true || $rawDeclic === 1 || $rawDeclic === "1" && !isset($parametres['valeur_declencheuse'])) {
            $declic = "";
        } else {
            $declic = trim((string)$rawDeclic);
        }

        // Si les colonnes n'existent pas dans la ligne, on ne touche à rien
        if (!$cible || !$source || !array_key_exists($source, $ligne)) {
            return $ligne;
        }

        $valeurCible = trim((string)($ligne[$cible] ?? ""));
        $valeurSource = trim((string)($ligne[$source] ?? ""));

        // On n'agit que si la source contient une information
        if ($valeurSource !== "") {
            // Logique de décision
            if ($declic !== 1 || $valeurCible === $declic) {
                $ligne[$cible] = $valeurSource;
            }
        }

        // NETTOYAGE : On supprime la colonne source (PK) du fichier final
        unset($ligne[$source]);

        return $ligne;
    }
}
