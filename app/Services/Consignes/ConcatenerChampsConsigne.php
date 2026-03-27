<?php

namespace App\Services\Consignes;

class ConcatenerChampsConsigne implements ConsigneInterface
{
    /**
     * Fusionne plusieurs champs dans un seul.
     * Exemple : ["Nom", "Prénom"] -> "Nom Prénom"
     */
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        $valeurs = [];

        // On récupère le séparateur depuis la base (ex: id 51 sur votre image)
        $separateur = $parametres['separateur'] ?? ' ';

        // On boucle sur les champs sources (ex: ['nom', 'prenom'])
        foreach ($champs as $champ) {
            if (isset($ligne[$champ]) && trim($ligne[$champ]) !== '') {
                $valeurs[] = trim($ligne[$champ]);
            }
        }

        // On fusionne avec le séparateur choisi
        $resultat = implode($separateur, $valeurs);

        // On définit le champ de destination (ex: 'Nom_prénom')
        $champCible = $parametres['champ_cible'] ?? 'champ_fusionne';

        $ligne[$champCible] = $resultat;

        return $ligne;
    }
}
