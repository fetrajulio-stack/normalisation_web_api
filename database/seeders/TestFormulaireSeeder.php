<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Formulaire;
use App\Models\ChampFormulaire;
use App\Models\RegleExport;
use App\Models\Enregistrement;

class TestFormulaireSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer un formulaire de test
        $formulaire = Formulaire::create([
            'nom' => 'Formulaire de Test - Enquête Film',
            'description' => 'Formulaire de test pour démonstration du système de transformation',
            'actif' => true
        ]);

        $this->command->info("✓ Formulaire créé (ID: {$formulaire->id})");

        // 2. Créer les champs
        $champs = [
            [
                'code_champ' => 'Q28',
                'libelle' => 'Tu es',
                'type_champ' => 'select',
                'valeurs_possibles' => ['1' => 'Garçon', '2' => 'Fille'],
                'obligatoire' => true,
                'ordre_affichage' => 1
            ],
            [
                'code_champ' => 'Q37-1',
                'libelle' => 'Revoir ce film au cinéma',
                'type_champ' => 'checkbox',
                'valeurs_possibles' => ['1' => 'Oui'],
                'ordre_affichage' => 2
            ],
            [
                'code_champ' => 'Q37-2',
                'libelle' => 'Acheter ce film en DVD / Blu-Ray / VOD',
                'type_champ' => 'checkbox',
                'valeurs_possibles' => ['1' => 'Oui'],
                'ordre_affichage' => 3
            ],
            [
                'code_champ' => 'Q37-3',
                'libelle' => 'Louer ce film en DVD / Blu-Ray / VOD',
                'type_champ' => 'checkbox',
                'valeurs_possibles' => ['1' => 'Oui'],
                'ordre_affichage' => 4
            ],
            [
                'code_champ' => 'Q37-4',
                'libelle' => 'Revoir ce film sur Disney+',
                'type_champ' => 'checkbox',
                'valeurs_possibles' => ['1' => 'Oui'],
                'ordre_affichage' => 5
            ],
            [
                'code_champ' => 'Q37-5',
                'libelle' => 'Ne pas revoir ce film',
                'type_champ' => 'checkbox',
                'valeurs_possibles' => ['1' => 'Oui'],
                'ordre_affichage' => 6
            ],
            [
                'code_champ' => 'cp',
                'libelle' => 'Code Postal',
                'type_champ' => 'text',
                'ordre_affichage' => 7
            ],
            [
                'code_champ' => 'Ville',
                'libelle' => 'Ville',
                'type_champ' => 'text',
                'ordre_affichage' => 8
            ]
        ];

        foreach ($champs as $champ) {
            ChampFormulaire::create(array_merge(['formulaire_id' => $formulaire->id], $champ));
        }

        $this->command->info("✓ {$formulaire->champs()->count()} champs créés");

        // 3. Créer les règles d'export

        // Règle 1 : Pas de transformation pour Q28 (juste pour l'exemple)
        RegleExport::create([
            'formulaire_id' => $formulaire->id,
            'nom_regle' => 'Aucune transformation Q28',
            'description' => 'Le champ Q28 reste inchangé',
            'priorite' => 1,
            'conditions' => null,
            'pipeline' => [],
            'actif' => true
        ]);

        // Règle 2 : Compactage des valeurs Q37-1 à Q37-5
        RegleExport::create([
            'formulaire_id' => $formulaire->id,
            'nom_regle' => 'Compacter Q37-1 à Q37-5',
            'description' => 'Déplace les valeurs "1" vers la gauche dans les champs Q37',
            'priorite' => 2,
            'conditions' => null,
            'pipeline' => [
                [
                    'operation' => 'compact_values',
                    'parametres' => [
                        'champs' => ['Q37-1', 'Q37-2', 'Q37-3', 'Q37-4', 'Q37-5'],
                        'valeur_cible' => '1',
                        'valeur_remplacement_vide' => ''
                    ]
                ]
            ],
            'actif' => true
        ]);

        // Règle 3 : Fusion CP + Ville = Adresse
        RegleExport::create([
            'formulaire_id' => $formulaire->id,
            'nom_regle' => 'Fusionner CP et Ville',
            'description' => 'Crée un champ Adresse à partir de CP et Ville',
            'priorite' => 3,
            'conditions' => null,
            'pipeline' => [
                [
                    'operation' => 'merge_fields',
                    'parametres' => [
                        'champs_sources' => ['cp', 'Ville'],
                        'champ_destination' => 'Adresse',
                        'separateur' => ' ',
                        'supprimer_sources' => true
                    ]
                ]
            ],
            'actif' => true
        ]);

        $this->command->info("✓ {$formulaire->regles()->count()} règles d'export créées");

        // 4. Créer des enregistrements de test
        $enregistrementsTest = [
            [
                'donnees' => [
                    'Q28' => '1',
                    'Q37-1' => '1',
                    'Q37-2' => '',
                    'Q37-3' => '',
                    'Q37-4' => '1',
                    'Q37-5' => '1',
                    'cp' => '75001',
                    'Ville' => 'Paris'
                ],
                'statut' => 'soumis'
            ],
            [
                'donnees' => [
                    'Q28' => '2',
                    'Q37-1' => '',
                    'Q37-2' => '1',
                    'Q37-3' => '',
                    'Q37-4' => '',
                    'Q37-5' => '1',
                    'cp' => '69002',
                    'Ville' => 'Lyon'
                ],
                'statut' => 'soumis'
            ],
            [
                'donnees' => [
                    'Q28' => '1',
                    'Q37-1' => '',
                    'Q37-2' => '',
                    'Q37-3' => '1',
                    'Q37-4' => '1',
                    'Q37-5' => '',
                    'cp' => '13001',
                    'Ville' => 'Marseille'
                ],
                'statut' => 'soumis'
            ]
        ];

        foreach ($enregistrementsTest as $enr) {
            Enregistrement::create(array_merge(['formulaire_id' => $formulaire->id], $enr));
        }

        $this->command->info("✓ {$formulaire->enregistrements()->count()} enregistrements de test créés");
        $this->command->newLine();
        $this->command->warn("Pour tester le système, exécutez :");
        $this->command->info("php artisan export:test-transformation {$formulaire->id}");
    }
}
