<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class CompactValuesOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champs = $parametres['champs'];
        $valeurCible = $parametres['valeur_cible'];
        $valeurVide = $parametres['valeur_remplacement_vide'] ?? '';

        // Collecter les valeurs qui correspondent à la valeur cible
        $valeursNonVides = [];
        foreach ($champs as $champ) {
            $valeur = $this->getValue($data, $champ);
            if ($valeur == $valeurCible) {
                $valeursNonVides[] = $valeurCible;
            }
        }

        // Redistribuer les valeurs en les "compactant" vers la gauche
        foreach ($champs as $index => $champ) {
            if (isset($valeursNonVides[$index])) {
                $this->setValue($data, $champ, $valeursNonVides[$index]);
            } else {
                $this->setValue($data, $champ, $valeurVide);
            }
        }

        return $data;
    }

    public function getDescription(): string
    {
        return "Compacte les valeurs non vides vers la gauche dans une série de champs";
    }

    public function getParametresSchema(): array
    {
        return [
            'champs' => [
                'type' => 'array',
                'required' => true,
                'description' => 'Liste ordonnée des champs à compacter'
            ],
            'valeur_cible' => [
                'type' => 'mixed',
                'required' => true,
                'description' => 'Valeur à considérer comme non vide'
            ],
            'valeur_remplacement_vide' => [
                'type' => 'mixed',
                'required' => false,
                'default' => '',
                'description' => 'Valeur à utiliser pour les positions vides'
            ]
        ];
    }
}
