<?php

namespace App\Services\Consignes;

class AjoutSeparateurConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // 🔹 Récupération du séparateur (par défaut "; ")
        $separator = $parametres['separateur'] ?? '; ';

        foreach ($champs as $champ) {

            // 🔒 sécurité
            if (!array_key_exists($champ, $ligne)) {
                continue;
            }

            $value = $ligne[$champ];

            // 🔹 Cas 1 : tableau (le plus propre)
            if (is_array($value)) {

                $ligne[$champ] = implode(
                    $separator,
                    array_filter($value, fn($v) => $v !== null && $v !== '')
                );
            }

            // 🔹 Cas 2 : string avec séparateurs multiples (ex: ",", ";")
            elseif (is_string($value)) {

                // découpage intelligent
                $parts = preg_split('/[;,]/', $value);

                if (is_array($parts)) {
                    $parts = array_map('trim', $parts);

                    $ligne[$champ] = implode(
                        $separator,
                        array_filter($parts)
                    );
                }
            }

            // 🔹 Cas 3 : valeur simple → rien à faire
        }

        return $ligne;
    }
}

