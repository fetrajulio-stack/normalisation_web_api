<?php

namespace App\Services;

class TabFilterService
{
    protected $normalizer;

    public function __construct(TextNormalizerService $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Filtre un tableau $tab1 en ne gardant que les clés présentes dans $tab2,
     * tout en normalisant les clés avec normalizeFieldName.
     *
     * @param array $tab1 Tableau source
     * @param array $tab2 Tableau des clés autorisées
     * @return array Tableau filtré et normalisé
     */
    public function filterAndNormalize(array $tab1, array $tab2): array
    {
        $result = [];

        foreach ($tab1 as $key => $value) {
            // Normaliser la clé
            $normalizedKey = $this->normalizer->normalizeFieldName($key);

            // Vérifier si la clé normalisée est dans $tab2
            if (in_array($normalizedKey, $tab2)) {
                $result[$normalizedKey] = $value;
            }
        }

        return $result;
    }
}
