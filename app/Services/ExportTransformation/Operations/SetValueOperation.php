<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class SetValueOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champ = $parametres['champ'];
        $valeur = $parametres['valeur'];

        $this->setValue($data, $champ, $valeur);

        return $data;
    }

    public function getDescription(): string
    {
        return "Définit une valeur fixe pour un champ";
    }

    public function getParametresSchema(): array
    {
        return [
            'champ' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Nom du champ'
            ],
            'valeur' => [
                'type' => 'mixed',
                'required' => true,
                'description' => 'Valeur à définir'
            ]
        ];
    }
}
