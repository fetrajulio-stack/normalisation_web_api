<?php

namespace App\Services\Consignes;

class FillIfNotEmptyConsigne
{
    /**
     * Applique la consigne :
     * Si champ renseigné → remplacer par une valeur paramétrée
     *
     * @param array $ligne       Une ligne de données
     * @param array $champs      Liste des champs concernés
     * @param array $parametres  Paramètres issus de parametre_consignes
     *
     * @return array
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // 🔹 Récupérer la valeur à saisir (clé: a_saisir)
        $valeurASaisir = $parametres['valeur'] ?? null;

        // 🔒 Sécurité : si pas de valeur définie, on ne fait rien
        if ($valeurASaisir === null) {
            return $ligne;
        }

        foreach ($champs as $champ) {

            // Vérifie que le champ existe et qu’il est renseigné
            if (
                array_key_exists($champ, $ligne) &&
                $ligne[$champ] !== null &&
                trim($ligne[$champ]) !== ''
            ) {
                // 🔥 Remplacer la valeur
                $ligne[$champ] = $valeurASaisir;
            }
        }

        return $ligne;
    }
}
