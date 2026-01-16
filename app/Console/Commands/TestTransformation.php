<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExportTransformation\TransformationEngine;
use App\Services\ExportTransformation\OperationRegistry;

class TestTransformation extends Command
{
    protected $signature = 'export:test-transformation {formulaire_id}';
    protected $description = 'Teste le moteur de transformation sur un formulaire';

    public function handle(TransformationEngine $engine, OperationRegistry $registry): int
    {
        $formulaireId = $this->argument('formulaire_id');

        $this->info("Test du moteur de transformation pour le formulaire #{$formulaireId}");
        $this->newLine();

        // Afficher les opérations disponibles
        $this->info("Opérations disponibles:");
        foreach ($registry->getOperationsInfo() as $info) {
            $this->line("  - {$info['code']}: {$info['description']}");
        }
        $this->newLine();

        // Transformer les enregistrements
        try {
            $this->info("Transformation des enregistrements...");
            $resultats = $engine->transformerPourExport($formulaireId);

            $this->info("✓ {$resultats->count()} enregistrements transformés");

            // Afficher les statistiques
            $stats = $engine->getStatistiquesRegles($formulaireId);
            if (!empty($stats)) {
                $this->newLine();
                $this->info("Statistiques d'exécution:");
                $this->table(
                    ['Règle', 'Exécutions', 'Succès', 'Échecs', 'Durée moy. (ms)'],
                    array_map(fn($s) => [
                        $s->nom_regle,
                        $s->nombre_executions,
                        $s->nombre_succes,
                        $s->nombre_echecs,
                        round($s->duree_moyenne_ms, 2)
                    ], $stats)
                );
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erreur: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
