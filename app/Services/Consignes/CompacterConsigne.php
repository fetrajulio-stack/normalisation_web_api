<?php

namespace App\Services\Consignes;

class CompacterConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // 1️⃣ Trier naturellement : Q35_1, Q35_2, ...
        natsort($champs);
        $champs = array_values($champs);

        $indices = [];

        foreach ($champs as $champ) {

            if (!array_key_exists($champ, $ligne)) {
                continue;
            }

            $valeur = trim((string) $ligne[$champ]);

            // ✅ Toute valeur non vide est prise
            if ($valeur !== '') {

                // Extraire le numéro après _
                if (preg_match('/_(\d+)$/', $champ, $match)) {
                    $indices[] = $match[1];
                }
            }
        }

        // 2️⃣ Réinjecter compacté vers l'avant
        foreach ($champs as $index => $champ) {
            $ligne[$champ] = $indices[$index] ?? '';
        }

        return $ligne;
    }
}
