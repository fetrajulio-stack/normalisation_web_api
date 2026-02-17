<?php

namespace App\Services\ExportTransformation;

use App\Models\RegleExport;
use App\Models\Enregistrement;
use App\Models\LogExecutionRegle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransformationEngine
{
    protected OperationRegistry $registry;
    protected ConditionEvaluator $evaluator;

    public function __construct(
        OperationRegistry $registry,
        ConditionEvaluator $evaluator
    ) {
        $this->registry = $registry;
        $this->evaluator = $evaluator;
    }

    /**
     * Transforme un seul enregistrement
     *
     * @param array $data Données de l'enregistrement
     * @param Collection $regles Collection de RegleExport
     * @param int|null $enregistrementId Pour le logging
     * @return array Données transformées
     */
    public function transformEnregistrement(
        array $data,
        Collection $regles,
        ?int $enregistrementId = null
    ): array {
        $resultat = $data;

        foreach ($regles as $regle) {
            $startTime = microtime(true);
            $succes = true;
            $erreur = null;

            try {
                // Évaluer les conditions
                if ($this->evaluator->evaluate($regle->conditions, $resultat)) {
                    // Appliquer le pipeline de la règle
                    $resultat = $this->executePipeline($resultat, $regle->pipeline);
                }
            } catch (\Exception $e) {
                $succes = false;
                $erreur = $e->getMessage();
                Log::error("Erreur lors de l'application de la règle {$regle->id}: " . $erreur, [
                    'regle_id' => $regle->id,
                    'enregistrement_id' => $enregistrementId,
                    'data' => $data
                ]);
            } finally {
                // Logger l'exécution
                if ($enregistrementId) {
                    $dureeMs = (int) ((microtime(true) - $startTime) * 1000);
                    $this->logExecution($regle->id, $enregistrementId, $succes, $erreur, $dureeMs);
                }
            }
        }

        return $resultat;
    }

    /**
     * Transforme plusieurs enregistrements
     *
     * @param Collection $enregistrements Collection d'Enregistrement
     * @param int $formulaireId
     * @return Collection Collection de données transformées
     */
    public function transformEnregistrements(Collection $enregistrements, int $formulaireId): Collection
    {
        // Charger toutes les règles actives du formulaire
        $regles = RegleExport::where('formulaire_id', $formulaireId)
            ->actif()
            ->ordrePriorite()
            ->get();

        if ($regles->isEmpty()) {
            // Pas de règles, retourner les données originales
            return $enregistrements->map(fn($enr) => $enr->donnees);
        }

        return $enregistrements->map(function ($enregistrement) use ($regles) {
            return $this->transformEnregistrement(
                $enregistrement->donnees,
                $regles,
                $enregistrement->id
            );
        });
    }

    /**
     * Exécute un pipeline d'opérations
     *
     * @param array $data
     * @param array $pipeline
     * @return array
     */
    protected function executePipeline(array $data, array $pipeline): array
    {
        $resultat = $data;

        foreach ($pipeline as $step) {
            $operationCode = $step['operation'] ?? null;
            $parametres = $step['parametres'] ?? [];

            if (!$operationCode) {
                throw new \InvalidArgumentException("Le pipeline doit contenir un code d'opération");
            }

            $operation = $this->registry->getOperation($operationCode);

            if (!$operation) {
                throw new \RuntimeException("Opération introuvable : {$operationCode}");
            }

            // Valider les paramètres
            $operation->validateParametres($parametres);

            // Exécuter l'opération
            $resultat = $operation->execute($resultat, $parametres);
        }

        return $resultat;
    }

    /**
     * Transforme et exporte directement
     *
     * @param int $formulaireId
     * @param string $statut Filtre par statut (optionnel)
     * @return Collection
     */
    public function transformerPourExport(int $formulaireId, string $statut = 'soumis'): Collection
    {
        $enregistrements = Enregistrement::where('formulaire_id', $formulaireId)
            ->where('statut', $statut)
            ->get();

        return $this->transformEnregistrements($enregistrements, $formulaireId);
    }

    /**
     * Teste une règle sur des données sans la sauvegarder
     *
     * @param array $data
     * @param array $regleConfig Configuration de la règle (conditions + pipeline)
     * @return array
     */
    public function testerRegle(array $data, array $regleConfig): array
    {
        $conditions = $regleConfig['conditions'] ?? null;
        $pipeline = $regleConfig['pipeline'] ?? [];

        // Vérifier les conditions
        if (!$this->evaluator->evaluate($conditions, $data)) {
            return [
                'appliquee' => false,
                'raison' => 'Conditions non remplies',
                'resultat' => $data
            ];
        }

        // Appliquer le pipeline
        try {
            $resultat = $this->executePipeline($data, $pipeline);

            return [
                'appliquee' => true,
                'resultat' => $resultat,
                'modifications' => $this->detecterModifications($data, $resultat)
            ];
        } catch (\Exception $e) {
            return [
                'appliquee' => false,
                'erreur' => $e->getMessage(),
                'resultat' => $data
            ];
        }
    }

    /**
     * Détecte les modifications entre deux ensembles de données
     */
    protected function detecterModifications(array $avant, array $apres): array
    {
        $modifications = [
            'ajoutes' => [],
            'modifies' => [],
            'supprimes' => []
        ];

        // Champs ajoutés
        foreach ($apres as $key => $value) {
            if (!array_key_exists($key, $avant)) {
                $modifications['ajoutes'][$key] = $value;
            } elseif ($avant[$key] !== $value) {
                $modifications['modifies'][$key] = [
                    'avant' => $avant[$key],
                    'apres' => $value
                ];
            }
        }

        // Champs supprimés
        foreach ($avant as $key => $value) {
            if (!array_key_exists($key, $apres)) {
                $modifications['supprimes'][$key] = $value;
            }
        }

        return $modifications;
    }

    /**
     * Log l'exécution d'une règle
     */
    protected function logExecution(
        int $regleId,
        int $enregistrementId,
        bool $succes,
        ?string $erreur,
        int $dureeMs
    ): void {
        try {
            LogExecutionRegle::create([
                'regle_id' => $regleId,
                'enregistrement_id' => $enregistrementId,
                'succes' => $succes,
                'erreur' => $erreur,
                'duree_ms' => $dureeMs
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors du logging de l'exécution: " . $e->getMessage());
        }
    }

    /**
     * Obtient les statistiques d'exécution des règles
     */
    public function getStatistiquesRegles(int $formulaireId): array
    {
        return DB::table('logs_execution_regles as logs')
            ->join('regles_export as regles', 'logs.regle_id', '=', 'regles.id')
            ->where('regles.formulaire_id', $formulaireId)
            ->select([
                'regles.id',
                'regles.nom_regle',
                DB::raw('COUNT(*) as nombre_executions'),
                DB::raw('SUM(CASE WHEN logs.succes = 1 THEN 1 ELSE 0 END) as nombre_succes'),
                DB::raw('SUM(CASE WHEN logs.succes = 0 THEN 1 ELSE 0 END) as nombre_echecs'),
                DB::raw('AVG(logs.duree_ms) as duree_moyenne_ms'),
                DB::raw('MAX(logs.duree_ms) as duree_max_ms')
            ])
            ->groupBy('regles.id', 'regles.nom_regle')
            ->get()
            ->toArray();
    }

    /**
     * Nettoie les anciens logs d'exécution
     */
    public function nettoyerLogsAnciens(int $joursConservation = 30): int
    {
        $date = now()->subDays($joursConservation);

        return LogExecutionRegle::where('created_at', '<', $date)->delete();
    }
}
