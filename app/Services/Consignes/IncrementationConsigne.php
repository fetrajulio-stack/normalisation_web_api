<?php

namespace App\Services\Consignes;

class IncrementationConsigne implements ConsigneInterface
{
    /**
     * Génère un numéro incrémenté.
     * Paramètres attendus dans la table parametre_consignes :
     * - 'start' : la valeur de départ (ex: 1)
     * - 'step' : le pas d'incrémentation (ex: 1)
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // On récupère la valeur de départ et le pas depuis la base de données
        $start = isset($parametres['start']) ? (int)$parametres['start'] : 1;
        $step = isset($parametres['step']) ? (int)$parametres['step'] : 1;

        // On utilise une variable statique pour garder la valeur en mémoire
        // pendant tout le cycle de traitement du fichier
        static $compteur = null;

        if ($compteur === null) {
            $compteur = $start;
        } else {
            $compteur += $step;
        }

        // Définir le champ cible
        $champCible = $parametres['champ_cible'] ?? ($champs[0] ?? 'num_ligne');

        // On peut aussi gérer un remplissage avec des zéros (ex: 001, 002) si besoin
        $ligne[$champCible] = $compteur;

        return $ligne;
    }
}
