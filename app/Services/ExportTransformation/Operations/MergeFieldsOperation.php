<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class MergeFieldsOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champsSource = $parametres['champs_sources'];
        $champDestination = $parametres['champ_destination'];
        $separateur = $parametres['separateur'] ?? ' ';
        $supprimerSources = $parametres['supprimer_sources'] ?? true;

        $valeurs = [];

        // Collecter les valeurs non vides
        foreach ($champsSource as $champ) {
            $valeur = $this->getValue($data, $champ);
            if ($valeur !== null && $valeur !== '') {
                $valeurs[] = $valeur;
            }
        }

        // Créer le champ fusionné
        $this->setValue($data, $champDestination, implode($separateur, $valeurs));

        // Supprimer les champs sources si demandé
        if ($supprimerSources) {
            foreach ($champsSource as $champ) {
                $this->removeField($data, $champ);
            }
        }

        return $data;
    }

    public function getDescription(): string
    {
        return "Fusionne plusieurs champs en un seul avec un séparateur personnalisable";
    }

    public function getParametresSchema(): array
    {
        return [
            'champs_sources' => [
                'type' => 'array',
                'required' => true,
                'description' => 'Liste des champs à fusionner'
            ],
            'champ_destination' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Nom du champ de destination'
            ],
            'separateur' => [
                'type' => 'string',
                'required' => false,
                'default' => ' ',
                'description' => 'Séparateur entre les valeurs'
            ],
            'supprimer_sources' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true,
                'description' => 'Supprimer les champs sources après fusion'
            ]
        ];
    }
}
