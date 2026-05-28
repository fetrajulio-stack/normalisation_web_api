<?php

namespace App\Services\Consignes;

class ExtraireNomLotConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $source = 'n_lot';

        if (!isset($ligne[$source])) {
            return $ligne;
        }

        $valeurSource = trim((string)$ligne[$source]);

        if ($valeurSource === '') {
            return $ligne;
        }

        $parties = explode('_', $valeurSource);

        foreach ($champs as $champId => $cible) {

            if (!isset($parametres[$champId])) {
                continue;
            }

            // reconstruction des paramètres du champ
            $params = [];

            foreach ($parametres[$champId] as $p) {
                $params[$p['cle']] = $p['valeur'];
            }

            $separateur = $params['separateur'] ?? '_';

            $position = isset($params['position'])
                ? (int)$params['position']
                : 0;

            // re-explode si séparateur différent (optionnel mais safe)
            if ($separateur !== '_') {
                $parties = explode($separateur, $valeurSource);
            }

            $ligne[$cible] = $parties[$position] ?? '';
        }

        return $ligne;
    }
}