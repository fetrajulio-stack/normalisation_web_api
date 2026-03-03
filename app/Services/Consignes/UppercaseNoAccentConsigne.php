<?php

namespace App\Services\Consignes;

class UppercaseNoAccentConsigne implements ConsigneInterface
{
    /**MAJUSCULE + SANS ACCENTS + PONCTUATION CONSERVÉE*/
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {
            if (!isset($ligne[$champ]) || $ligne[$champ] === null) {
                continue;
            }

            $valeur = (string) $ligne[$champ];

            // 1. Supprimer les accents
            $valeur = $this->removeAccents($valeur);

            // 2. Mettre en MAJUSCULE UTF-8
            $ligne[$champ] = mb_strtoupper($valeur, 'UTF-8');
        }

        return $ligne;
    }

    /**
     * Supprime les accents sans toucher à la ponctuation
     */
    private function removeAccents(string $text): string
    {
        $accents = [
            'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A',
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',

            'Ç'=>'C','ç'=>'c',

            'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',

            'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',

            'Ñ'=>'N','ñ'=>'n',

            'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
            'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',

            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',

            'Ý'=>'Y','ý'=>'y','ÿ'=>'y',
        ];

        return strtr($text, $accents);
    }

}
