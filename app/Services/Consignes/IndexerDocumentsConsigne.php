<?php

namespace App\Services\Consignes;

class IndexerDocumentsConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {

        $items = [];
        
        // Uniformiser les clés de la ligne
        $ligne = array_change_key_case($ligne, CASE_LOWER);

        // =====================================================
        // FORMAT 1 :
        // [
        //   ['champ'=>'type_acte','position'=>1,'separateur'=>'_']
        // ]
        // =====================================================
        if ($this->isFlatParamList($parametres)) {

            foreach ($parametres as $param) {

                if (!is_array($param) || empty($param['champ'])) {
                    continue;
                }

                $champName = strtolower(trim((string) $param['champ']));
                $value = trim((string) ($ligne[$champName] ?? ''));

                if ($value === '') {
                    continue;
                }

                $items[] = [
                    'value'     => $value,
                    'position'  => $this->normalizePosition($param['position'] ?? null),
                    'separator' => (string) ($param['separateur'] ?? ''),
                ];
            }
        
        } else {
            foreach ($parametres as $champId => $entries) {

            // Nouveau format venant du contrôleur :
            // [
            //   12 => [
            //      ['cle'=>'position','valeur'=>'1'],
            //      ['cle'=>'separateur','valeur'=>'_']
            //   ]
            // ]

            if (
                is_numeric($champId)
                && isset($champs[$champId])
                && is_array($entries)
            ) {

                $fieldKey = strtolower($champs[$champId]);

                $value = trim((string) ($ligne[$fieldKey] ?? ''));

                if ($value === '') {
                    continue;
                }

                $position = PHP_INT_MAX;
                $separator = '';

                foreach ($entries as $entry) {

                    if (!is_array($entry)) {
                        continue;
                    }

                    if (($entry['cle'] ?? '') === 'position') {
                        $position = $this->normalizePosition(
                            $entry['valeur'] ?? null
                        );
                    }

                    if (($entry['cle'] ?? '') === 'separateur') {
                        $separator = (string) ($entry['valeur'] ?? '');
                    }
                }

                $items[] = [
                    'value'     => $value,
                    'position'  => $position,
                    'separator' => $separator,
                ];

                continue;
            }

            // ancien traitement...
        }
           
           
        }

        if (empty($items)) {
            return $ligne;
        }

        // Tri par position
        usort($items, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        // Construction du nom de fichier
        $nomFichier = '';
        $count = count($items);

        foreach ($items as $index => $item) {

            $nomFichier .= $item['value'];

            if (
                $index < ($count - 1)
                && !empty($item['separator'])
            ) {
                $nomFichier .= $item['separator'];
            }
        }

        if ($nomFichier !== '') {
            $ligne['nom_fichier_indexe'] = $nomFichier . '.pdf';
        }
        
        return $ligne;

    }

    private function isFlatParamList(array $parametres): bool
    {
        return array_values($parametres) === $parametres
            && isset($parametres[0])
            && is_array($parametres[0])
            && array_key_exists('champ', $parametres[0]);
    }

    private function normalizePosition($position): int
    {
        if (is_numeric($position)) {
            return (int) $position;
        }

        if (is_string($position) && trim($position) !== '') {
            return (int) filter_var(
                $position,
                FILTER_SANITIZE_NUMBER_INT
            );
        }

        return PHP_INT_MAX;
    }
}