<?php

namespace App\Services\Consignes;

class AjoutSeparateurConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {


        $separator = $parametres['separateur'] ?? '; ';

        foreach ($champs as $champ) {

            if (!array_key_exists($champ, $ligne)) {
                continue;
            }

            $value = $ligne[$champ];

            // 🔹 Cas 1 : tableau
            if (is_array($value)) {

                $ligne[$champ] = implode(
                    $separator,
                    array_filter($value, fn($v) => $v !== null && $v !== '')
                );
            }

            // 🔹 Cas 2 : string
            elseif (is_string($value)) {

                $value = trim($value);

                // 🔥 CAS 1 : contient , ou ;
                if (preg_match('/[,;]/', $value)) {

                    $parts = preg_split('/[;,]/', $value);

                }
                // 🔥 CAS 2 : chaîne compacte (ex: "21")
                elseif (ctype_digit($value) && strlen($value) > 1) {

                    // ⚠️ important : découper caractère par caractère
                    $parts = str_split($value);

                }
                else {
                    continue;
                }

                // 🔹 nettoyage + reconstruction
                $parts = array_map('trim', $parts);

                $ligne[$champ] = implode(
                    $separator,
                    array_filter($parts)
                );
            }
        }

        return $ligne;
    }
}

