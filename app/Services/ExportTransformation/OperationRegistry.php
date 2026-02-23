<?php

namespace App\Services\ExportTransformation;

use App\Models\OperationPrimitive;
use App\Services\ExportTransformation\Contracts\OperationInterface;
use Illuminate\Support\Facades\Cache;

class OperationRegistry
{
    protected array $operations = [];
    protected bool $loaded = false;

    /**
     * Charge toutes les opérations depuis la base de données
     */
    public function loadOperations(): void
    {
        if ($this->loaded) {
            return;
        }

        // Cache les opérations pendant 1 heure
        $operationsPrimitives = Cache::remember('operations_primitives', 3600, function () {
            return OperationPrimitive::actif()->get();
        });

        foreach ($operationsPrimitives as $operation) {
            try {
                $this->registerOperation($operation->code, $operation->getInstance());
            } catch (\Exception $e) {
                \Log::error("Erreur lors du chargement de l'opération {$operation->code}: " . $e->getMessage());
            }
        }

        $this->loaded = true;
    }

    /**
     * Enregistre une opération manuellement
     */
    public function registerOperation(string $code, OperationInterface $operation): void
    {
        $this->operations[$code] = $operation;
    }

    /**
     * Récupère une opération par son code
     */
    public function getOperation(string $code): ?OperationInterface
    {
        $this->loadOperations();

        return $this->operations[$code] ?? null;
    }

    /**
     * Vérifie si une opération existe
     */
    public function hasOperation(string $code): bool
    {
        $this->loadOperations();

        return isset($this->operations[$code]);
    }

    /**
     * Retourne toutes les opérations enregistrées
     */
    public function getAllOperations(): array
    {
        $this->loadOperations();

        return $this->operations;
    }

    /**
     * Retourne la liste des codes d'opérations disponibles
     */
    public function getAvailableOperations(): array
    {
        $this->loadOperations();

        return array_keys($this->operations);
    }

    /**
     * Vide le cache des opérations
     */
    public function clearCache(): void
    {
        Cache::forget('operations_primitives');
        $this->operations = [];
        $this->loaded = false;
    }

    /**
     * Retourne les informations sur toutes les opérations
     */
    public function getOperationsInfo(): array
    {
        $this->loadOperations();

        $info = [];
        foreach ($this->operations as $code => $operation) {
            $info[$code] = [
                'code' => $code,
                'description' => $operation->getDescription(),
                'parametres' => $operation->getParametresSchema(),
            ];
        }

        return $info;
    }
}
