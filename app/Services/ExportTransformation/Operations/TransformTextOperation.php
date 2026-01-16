<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;

class TransformTextOperation extends BaseOperation
{
    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $champs = $parametres['champs'];
        $transformation = $parametres['transformation'];

        foreach ($champs as $champ) {
            $valeur = $this->getValue($data, $champ);

            if ($valeur !== null && is_string($valeur)) {
                $valeurTransformee = $this->appliquerTransformation($valeur, $transformation);
                $this->setValue($data, $champ, $valeurTransformee);
            }
        }

        return $data;
    }

    protected function appliquerTransformation(string $valeur, string $transformation): string
    {
        return match($transformation) {
            'uppercase' => strtoupper($valeur),
            'lowercase' => strtolower($valeur),
            'ucfirst' => ucfirst(strtolower($valeur)),
            'ucwords' => ucwords(strtolower($valeur)),
            'trim' => trim($valeur),
            'capitalize' => mb_convert_case($valeur, MB_CASE_TITLE, 'UTF-8'),
            default => $valeur,
        };
    }

    public function getDescription(): string
    {
        return "Applique une transformation de texte sur un ou plusieurs champs";
    }

    public function getParametresSchema(): array
    {
        return [
            'champs' => [
                'type' => 'array',
                'required' => true,
                'description' => 'Liste des champs à transformer'
            ],
            'transformation' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Type de transformation (uppercase, lowercase, ucfirst, ucwords, trim, capitalize)'
            ]
        ];
    }
}
