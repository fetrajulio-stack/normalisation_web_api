<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class FilterEmptyOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champs = $parametres['champs'] ?? array_keys($data);

        foreach ($champs as $champ) {
            $valeur = $this->getValue($data, $champ);

            if ($valeur === null || $valeur === '') {
                $this->removeField($data, $champ);
            }
        }

        return $data;
    }

    public function getDescription(): string
    {
        return "Supprime les champs vides ou null";
    }

    public function getParametresSchema(): array
    {
        return [
            'champs' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Liste des champs à filtrer (tous si non spécifié)'
            ]
        ];
    }
}
