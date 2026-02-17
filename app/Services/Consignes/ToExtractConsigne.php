<?php

namespace App\Services\Consignes;

class ToExtractConsigne implements ConsigneInterface
{
    /**
     * @param array $ligne       Ligne de données à enrichir
     * @param array $champs      Champs à remplir avec la valeur extraite
     * @param array $parametres  [
     *     'source' => string,   // nom du lot
     *     'pattern' => string,  // regex d'extraction
     *     'default' => string   // valeur par défaut si extraction échoue
     * ]
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {

        $source  = $parametres['source'] ?? '';
        $pattern = $parametres['pattern'] ?? null;
        $default = $parametres['default'] ?? null;
        $valeurExtraite = $default;


        if ($source && $pattern) {
            if (preg_match($pattern, $source, $matches)) {
                $valeurExtraite = $matches[1] ?? $matches[0];
            }
        }

        foreach ($champs as $champ) {
            if (
                !isset($ligne[$champ]) ||
                $ligne[$champ] === null ||
                trim($ligne[$champ]) === ''
            ) {
                $ligne[$champ] = $valeurExtraite;
            }
        }



        return $ligne;
    }
}
