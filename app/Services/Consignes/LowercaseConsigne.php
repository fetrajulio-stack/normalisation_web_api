<?php
namespace App\Services\Consignes;

class LowercaseConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {
            if (isset($ligne[$champ])) {
                $ligne[$champ] = strtolower($ligne[$champ]);
            }
        }

        return $ligne;
    }
}

