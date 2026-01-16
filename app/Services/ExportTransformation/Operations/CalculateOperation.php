<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class CalculateOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champsSources = $parametres['champs_sources'];
        $champDestination = $parametres['champ_destination'];
        $operation = $parametres['operation'];

        $valeurs = [];
        foreach ($champsSources as $champ) {
            $valeur = $this->getValue($data, $champ);
            if (is_numeric($valeur)) {
                $valeurs[] = (float) $valeur;
            }
        }

        if (empty($valeurs)) {
            $this->setValue($data, $champDestination, null);
            return $data;
        }

        $resultat = $this->calculer($valeurs, $operation);
        $this->setValue($data, $champDestination, $resultat);

        return $data;
    }

    protected function calculer(array $valeurs, string $operation): float
    {
        return match($operation) {
            'sum' => array_sum($valeurs),
            'average', 'avg' => array_sum($valeurs) / count($valeurs),
            'min' => min($valeurs),
            'max' => max($valeurs),
            'multiply' => array_product($valeurs),
            'count' => count($valeurs),
            default => 0,
        };
    }

    public function getDescription(): string
    {
        return "Effectue un calcul sur plusieurs champs numériques";
    }

    public function getParametresSchema(): array
    {
        return [
            'champs_sources' => [
                'type' => 'array',
                'required' => true,
                'description' => 'Champs numériques à utiliser'
            ],
            'champ_destination' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Champ où stocker le résultat'
            ],
            'operation' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Type de calcul (sum, average, min, max, multiply, count)'
            ]
        ];
    }
}
