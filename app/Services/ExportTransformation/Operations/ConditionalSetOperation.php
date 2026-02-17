<?php

namespace App\Services\ExportTransformation\Operations;

use App\Services\ExportTransformation\Contracts\BaseOperation;
use App\Services\ExportTransformation\ConditionEvaluator;

class ConditionalSetOperation extends BaseOperation
{
    protected ConditionEvaluator $evaluator;

    public function __construct()
    {
        $this->evaluator = new ConditionEvaluator();
    }

    public function execute(array $data, array $parametres): array
    {
        $this->validateParametres($parametres);

        $condition = $parametres['condition'];
        $champ = $parametres['champ'];
        $valeurSiVrai = $parametres['valeur_si_vrai'];
        $valeurSiFaux = $parametres['valeur_si_faux'] ?? null;

        $resultatCondition = $this->evaluator->evaluate($condition, $data);

        if ($resultatCondition) {
            $this->setValue($data, $champ, $valeurSiVrai);
        } elseif ($valeurSiFaux !== null) {
            $this->setValue($data, $champ, $valeurSiFaux);
        }

        return $data;
    }

    public function getDescription(): string
    {
        return "Définit une valeur selon une condition";
    }

    public function getParametresSchema(): array
    {
        return [
            'condition' => [
                'type' => 'object',
                'required' => true,
                'description' => 'Condition à évaluer'
            ],
            'champ' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Champ à modifier'
            ],
            'valeur_si_vrai' => [
                'type' => 'mixed',
                'required' => true,
                'description' => 'Valeur si condition vraie'
            ],
            'valeur_si_faux' => [
                'type' => 'mixed',
                'required' => false,
                'description' => 'Valeur si condition fausse'
            ]
        ];
    }
}
