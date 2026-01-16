<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OperationPrimitive;

class OperationsPrimitivesSeeder extends Seeder
{
    public function run(): void
    {
        $operations = [
            [
                'code' => 'merge_fields',
                'nom' => 'Fusionner des champs',
                'description' => 'Fusionne plusieurs champs en un seul avec un séparateur',
                'parametres_schema' => [
                    'champs_sources' => ['type' => 'array', 'required' => true],
                    'champ_destination' => ['type' => 'string', 'required' => true],
                    'separateur' => ['type' => 'string', 'required' => false, 'default' => ' '],
                    'supprimer_sources' => ['type' => 'boolean', 'required' => false, 'default' => true]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\MergeFieldsOperation',
                'actif' => true
            ],
            [
                'code' => 'compact_values',
                'nom' => 'Compacter les valeurs',
                'description' => 'Déplace les valeurs non vides vers la gauche',
                'parametres_schema' => [
                    'champs' => ['type' => 'array', 'required' => true],
                    'valeur_cible' => ['type' => 'mixed', 'required' => true],
                    'valeur_remplacement_vide' => ['type' => 'mixed', 'required' => false, 'default' => '']
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\CompactValuesOperation',
                'actif' => true
            ],
            [
                'code' => 'map_value',
                'nom' => 'Mapper les valeurs',
                'description' => 'Transforme les valeurs selon un mapping',
                'parametres_schema' => [
                    'champ' => ['type' => 'string', 'required' => true],
                    'mapping' => ['type' => 'object', 'required' => true],
                    'valeur_par_defaut' => ['type' => 'mixed', 'required' => false]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\MapValueOperation',
                'actif' => true
            ],
            [
                'code' => 'set_value',
                'nom' => 'Définir une valeur',
                'description' => 'Définit une valeur fixe pour un champ',
                'parametres_schema' => [
                    'champ' => ['type' => 'string', 'required' => true],
                    'valeur' => ['type' => 'mixed', 'required' => true]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\SetValueOperation',
                'actif' => true
            ],
            [
                'code' => 'transform_text',
                'nom' => 'Transformer le texte',
                'description' => 'Applique une transformation de texte',
                'parametres_schema' => [
                    'champs' => ['type' => 'array', 'required' => true],
                    'transformation' => ['type' => 'string', 'required' => true]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\TransformTextOperation',
                'actif' => true
            ],
            [
                'code' => 'filter_empty',
                'nom' => 'Filtrer les vides',
                'description' => 'Supprime les champs vides',
                'parametres_schema' => [
                    'champs' => ['type' => 'array', 'required' => false]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\FilterEmptyOperation',
                'actif' => true
            ],
            [
                'code' => 'split_field',
                'nom' => 'Diviser un champ',
                'description' => 'Divise un champ en plusieurs',
                'parametres_schema' => [
                    'champ_source' => ['type' => 'string', 'required' => true],
                    'champs_destination' => ['type' => 'array', 'required' => true],
                    'separateur' => ['type' => 'string', 'required' => true],
                    'supprimer_source' => ['type' => 'boolean', 'required' => false, 'default' => true]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\SplitFieldOperation',
                'actif' => true
            ],
            [
                'code' => 'calculate',
                'nom' => 'Calculer',
                'description' => 'Effectue un calcul sur des champs',
                'parametres_schema' => [
                    'champs_sources' => ['type' => 'array', 'required' => true],
                    'champ_destination' => ['type' => 'string', 'required' => true],
                    'operation' => ['type' => 'string', 'required' => true]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\CalculateOperation',
                'actif' => true
            ],
            [
                'code' => 'conditional_set',
                'nom' => 'Définir selon condition',
                'description' => 'Définit une valeur selon une condition',
                'parametres_schema' => [
                    'condition' => ['type' => 'object', 'required' => true],
                    'champ' => ['type' => 'string', 'required' => true],
                    'valeur_si_vrai' => ['type' => 'mixed', 'required' => true],
                    'valeur_si_faux' => ['type' => 'mixed', 'required' => false]
                ],
                'classe_php' => 'App\\Services\\ExportTransformation\\Operations\\ConditionalSetOperation',
                'actif' => true
            ]
        ];

        foreach ($operations as $operation) {
            OperationPrimitive::updateOrCreate(
                ['code' => $operation['code']],
                $operation
            );
        }

        $this->command->info('✓ Opérations primitives créées avec succès');
    }
}
