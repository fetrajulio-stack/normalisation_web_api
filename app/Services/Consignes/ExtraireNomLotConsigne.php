<?php

namespace App\Services\Consignes;

class ExtraireNomLotConsigne implements ConsigneInterface

{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        // 1. Récupération du nom du lot
        $nomLot = $parametres['nom_lot'] ?? ($ligne['nom_lot'] ?? '');

        // 2. Récupération dynamique des réglages depuis ta table parametre_consignes
        // On utilise les clés que l'on voit sur ton image PHPMyAdmin
        $separateur = $parametres['separateur'] ?? '_';
        $position = isset($parametres['position']) ? (int)$parametres['position'] : 3;

        if (empty($nomLot)) {
            return $ligne;
        }

        // 3. Logique d'extraction dynamique
        // On découpe selon le séparateur défini en base (ex: '_')
        $parties = explode($separateur, $nomLot);

        // On récupère la partie à la position demandée (ex: index 3 pour la 4ème partie)
        $resultat = isset($parties[$position]) ? trim($parties[$position]) : '';

        // 4. Définition du champ cible
        $champCible = $parametres['champ_cible'] ?? ($champs[0] ?? 'nom_extrait');
        $ligne[$champCible] = $resultat;

        return $ligne;
    }
}
