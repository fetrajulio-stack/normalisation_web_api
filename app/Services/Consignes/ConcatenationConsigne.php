<?php
namespace App\Services\Consignes;

class ConcatenationConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {

        // champ destination (ex: adresse)
        $champCible = $parametres['champ_cible'] ?? null;
        $separateur = $parametres['separateur'] ?? ' ';

       if (!$champCible) {
            throw new \Exception("Champ cible manquant pour la consigne CONCATENER");
        }

        $valeurs = [];

        foreach ($champs as $champ) {
            if (!empty($ligne[$champ])) {
                $valeurs[] = trim($ligne[$champ]);
            }
        }

        $ligne[$champCible] = implode($separateur, $valeurs);

        // 🔹 Supprimer les champs d'origine concaténés
        foreach ($champs as $champ) {
            unset($ligne[$champ]);
        }
      //  dd($ligne);
        return $ligne;
    }
}
