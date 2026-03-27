<?php

namespace App\Services;

use Illuminate\Support\Str;
use mb_string;

class FixedLengthService
{
    /**
     * Formate une collection de données en texte à longueur fixe
     */
    public function generate($config, $records): string
    {
        $output = "";

        foreach ($records as $record) {
            $line = "";

            // On convertit l'objet record en tableau avec clés en minuscules
            $data = array_change_key_case((array)$record, CASE_LOWER);

            foreach ($config as $map) {

                // 1. La LONGUEUR vient de la table datamaps
                $longueur = $map->longueur;

                // 2. Le NOM DU CHAMP vient de la relation avec la table champs
                $value = '';
                if ($map->champ) {
                    $fieldName = strtolower($map->champ->nom_champ);

                    // 3. La VALEUR RÉELLE vient record
                    $value = $data[$fieldName] ?? '';
                }

                // 4. ON APPLIQUE LE FORMATAGE (Tronquer + Espaces de remplissage)
                $value = \Illuminate\Support\Str::ascii((string)$value);

                $line .= str_pad(
                    mb_substr($value, 0, $longueur),
                    $longueur,
                    " ",
                    STR_PAD_RIGHT
                );

                // --- FIN DU PONT ---
            }

            $output .= $line . "\r\n";
        }

        return $output;
    }
}
