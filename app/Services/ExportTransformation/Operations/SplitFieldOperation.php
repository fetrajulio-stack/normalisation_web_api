<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class SplitFieldOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champSource = $parametres['champ_source'];
        $champsDestination = $parametres['champs_destination'];
        $separateur = $parametres['separateur'];
        $supprimerSource = $parametres['supprimer_source'] ?? true;

        $valeur = $this->getValue($data, $champSource, '');
        $parties = explode($separateur, $valeur);

        // Distribuer les parties dans les champs de destination
        foreach ($champsDestination as $index => $champDest) {
            $valeurPartie = $parties[$index] ?? '';
            $this->setValue($data, $champDest, trim($valeurPartie));
        }

        // Supprimer le champ source si demandé
        if ($supprimerSource) {
            $this->removeField($data, $champSource);
        }

        return $data;
    }

    public function getDescription(): string
    {
        return "Divise un champ en plusieurs selon un séparateur";
    }

    public function getParametresSchema(): array
    {
        return [
            'champ_source' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Champ à diviser'
            ],
            'champs_destination' => [
                'type' => 'array',
                'required' => true,
                'description' => 'Liste des champs de destination'
            ],
            'separateur' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Séparateur pour diviser le champ'
            ],
            'supprimer_source' => [
                'type' => 'boolean',
                'required' => false,
                'default' => true,
                'description' => 'Supprimer le champ source après division'
            ]
        ];
    }
}
