<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class MapValueOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champ = $parametres['champ'];
        $mapping = $parametres['mapping'];
        $defaultValue = $parametres['valeur_par_defaut'] ?? null;

        $valeurActuelle = $this->getValue($data, $champ);

        if ($valeurActuelle !== null && isset($mapping[$valeurActuelle])) {
            $this->setValue($data, $champ, $mapping[$valeurActuelle]);
        } elseif ($defaultValue !== null) {
            $this->setValue($data, $champ, $defaultValue);
        }

        return $data;
    }

    public function getDescription(): string
    {
        return "Transforme les valeurs d'un champ selon un mapping prédéfini";
    }

    public function getParametresSchema(): array
    {
        return [
            'champ' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Champ à transformer'
            ],
            'mapping' => [
                'type' => 'object',
                'required' => true,
                'description' => 'Table de correspondance clé => valeur'
            ],
            'valeur_par_defaut' => [
                'type' => 'mixed',
                'required' => false,
                'description' => 'Valeur par défaut si aucune correspondance'
            ]
        ];
    }
}
