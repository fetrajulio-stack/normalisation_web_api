<?php

namespace App\Services\ExportTransformation;

class ConditionEvaluator
{
    /**
     * Évalue une condition sur des données
     *
     * @param array|null $condition Structure de condition JSON
     * @param array $data Données de l'enregistrement
     * @return bool
     */
    public function evaluate(?array $condition, array $data): bool
    {
        // Si pas de condition, toujours vrai
        if (empty($condition)) {
            return true;
        }

        // Si c'est un opérateur logique (AND, OR)
        if (isset($condition['operator'])) {
            return $this->evaluateLogicalOperator($condition, $data);
        }

        // Sinon c'est une condition simple
        return $this->evaluateSimpleCondition($condition, $data);
    }

    /**
     * Évalue un opérateur logique (AND, OR, NOT)
     */
    protected function evaluateLogicalOperator(array $condition, array $data): bool
    {
        $operator = strtoupper($condition['operator']);
        $rules = $condition['rules'] ?? [];

        switch ($operator) {
            case 'AND':
                foreach ($rules as $rule) {
                    if (!$this->evaluate($rule, $data)) {
                        return false;
                    }
                }
                return true;

            case 'OR':
                foreach ($rules as $rule) {
                    if ($this->evaluate($rule, $data)) {
                        return true;
                    }
                }
                return false;

            case 'NOT':
                // NOT prend une seule règle
                $rule = $rules[0] ?? $condition['rule'] ?? null;
                if ($rule) {
                    return !$this->evaluate($rule, $data);
                }
                return true;

            default:
                throw new \InvalidArgumentException("Opérateur logique inconnu : {$operator}");
        }
    }

    /**
     * Évalue une condition simple
     */
    protected function evaluateSimpleCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if ($field === null) {
            return true;
        }

        $fieldValue = $this->getFieldValue($data, $field);

        return $this->compareValues($fieldValue, $operator, $value);
    }

    /**
     * Récupère la valeur d'un champ (supporte la notation pointée)
     */
    protected function getFieldValue(array $data, string $field)
    {
        // Support de la notation pointée (ex: "user.age")
        if (strpos($field, '.') !== false) {
            $keys = explode('.', $field);
            $value = $data;

            foreach ($keys as $key) {
                if (!isset($value[$key])) {
                    return null;
                }
                $value = $value[$key];
            }

            return $value;
        }

        return $data[$field] ?? null;
    }

    /**
     * Compare deux valeurs selon un opérateur
     */
    protected function compareValues($fieldValue, string $operator, $compareValue): bool
    {
        switch ($operator) {
            case '=':
            case '==':
            case 'equals':
                return $fieldValue == $compareValue;

            case '!=':
            case '<>':
            case 'not_equals':
                return $fieldValue != $compareValue;

            case '>':
            case 'greater_than':
                return $fieldValue > $compareValue;

            case '>=':
            case 'greater_or_equal':
                return $fieldValue >= $compareValue;

            case '<':
            case 'less_than':
                return $fieldValue < $compareValue;

            case '<=':
            case 'less_or_equal':
                return $fieldValue <= $compareValue;

            case 'in':
                return is_array($compareValue) && in_array($fieldValue, $compareValue);

            case 'not_in':
                return is_array($compareValue) && !in_array($fieldValue, $compareValue);

            case 'contains':
                return is_string($fieldValue) && is_string($compareValue)
                    && strpos($fieldValue, $compareValue) !== false;

            case 'not_contains':
                return is_string($fieldValue) && is_string($compareValue)
                    && strpos($fieldValue, $compareValue) === false;

            case 'starts_with':
                return is_string($fieldValue) && is_string($compareValue)
                    && strpos($fieldValue, $compareValue) === 0;

            case 'ends_with':
                return is_string($fieldValue) && is_string($compareValue)
                    && substr($fieldValue, -strlen($compareValue)) === $compareValue;

            case 'is_null':
            case 'null':
                return $fieldValue === null;

            case 'is_not_null':
            case 'not_null':
                return $fieldValue !== null;

            case 'is_empty':
            case 'empty':
                return empty($fieldValue);

            case 'is_not_empty':
            case 'not_empty':
                return !empty($fieldValue);

            case 'between':
                if (is_array($compareValue) && count($compareValue) === 2) {
                    return $fieldValue >= $compareValue[0] && $fieldValue <= $compareValue[1];
                }
                return false;

            case 'regex':
            case 'matches':
                return is_string($fieldValue) && is_string($compareValue)
                    && preg_match($compareValue, $fieldValue) === 1;

            default:
                throw new \InvalidArgumentException("Opérateur de comparaison inconnu : {$operator}");
        }
    }

    /**
     * Valide la structure d'une condition
     */
    public function validateCondition(array $condition): bool
    {
        if (isset($condition['operator'])) {
            $operator = strtoupper($condition['operator']);

            if (!in_array($operator, ['AND', 'OR', 'NOT'])) {
                throw new \InvalidArgumentException("Opérateur logique invalide : {$operator}");
            }

            $rules = $condition['rules'] ?? [];
            if (empty($rules)) {
                throw new \InvalidArgumentException("L'opérateur {$operator} nécessite des règles");
            }

            foreach ($rules as $rule) {
                $this->validateCondition($rule);
            }

            return true;
        }

        // Validation d'une condition simple
        if (!isset($condition['field'])) {
            throw new \InvalidArgumentException("Une condition simple doit avoir un champ 'field'");
        }

        return true;
    }
}
