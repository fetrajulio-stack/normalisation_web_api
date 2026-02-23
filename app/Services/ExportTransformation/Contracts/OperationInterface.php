<?php

namespace App\Services\ExportTransformation\Contracts;

interface OperationInterface
{
    /**
     * Exécute l'opération sur les données
     *
     * @param array $data Les données de l'enregistrement
     * @param array $parametres Les paramètres de l'opération
     * @return array Les données transformées
     * @throws \Exception Si l'opération échoue
     */
    public function execute(array $data, array $parametres): array;

    /**
     * Valide les paramètres de l'opération
     *
     * @param array $parametres
     * @return bool
     * @throws \InvalidArgumentException Si les paramètres sont invalides
     */
    public function validateParametres(array $parametres): bool;

    /**
     * Retourne la description de l'opération
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Retourne le schéma des paramètres attendus
     *
     * @return array
     */
    public function getParametresSchema(): array;
}
