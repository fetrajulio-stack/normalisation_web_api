<?php
namespace App\Services;

use App\Services\Consignes\CompacterConsigne;
use App\Services\Consignes\ConcatenationConsigne;
use App\Services\Consignes\FillemptyConsigne;
use App\Services\Consignes\LowercaseConsigne;
use App\Services\Consignes\PrefixConsigne;
use App\Services\Consignes\SiAutreChampRenseigneConsigne;
use App\Services\Consignes\SiVideAlorsConsigne;
use App\Services\Consignes\UppercaseConsigne;
use App\Services\Consignes\CompactageConsigne;

class ConsigneExecutor
{
    public function getHandler(string $code)
    {
        return match ($code) {
            'LOWERCASE' => new LowercaseConsigne(),
            'UPPERCASE' => new UppercaseConsigne(),
            'FILL_EMPTY' => new FillemptyConsigne(),
            'CONCATENATE' => new ConcatenationConsigne(),
            'COMPACTER' => new CompacterConsigne(),
            'PREFIX' => new PrefixConsigne(),
            'SI_VIDE_ALORS' => new SiVideAlorsConsigne(),
            'SI_AUTRE_CHAMP_RENSEIGNE' => new SiAutreChampRenseigneConsigne(),
            default => throw new \Exception("Consigne inconnue : $code")
        };
    }
}

