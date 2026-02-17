<?php


namespace App\Services;

class TextNormalizerService
{
    /**
     * Normalise un texte pour en faire un nom de colonne ou un identifiant propre.
     */
    public function normalizeFieldName(string $text): string
    {
        $text = trim($text);

        // Supprimer uniquement les accents (tout en conservant la lettre)
        $replace = [
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
            'æ'=>'ae','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n',
            'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
            'ý'=>'y','ÿ'=>'y',
            'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A',
            'Æ'=>'AE','Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
            'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ñ'=>'N',
            'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O',
            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
            'Ý'=>'Y',
        ];
        $text = strtr($text, $replace);

        // Mettre en minuscules
        $text = strtolower($text);

        // Remplacer tout ce qui n'est pas lettre, chiffre ou _ par espace
        $text = preg_replace('/[^a-z0-9_]+/u', ' ', $text);

        // Remplacer les espaces par _
        $text = preg_replace('/\s+/u', '_', $text);

        // Supprimer les _ en trop
        $text = trim($text, '_');

        return $text;
    }
}

