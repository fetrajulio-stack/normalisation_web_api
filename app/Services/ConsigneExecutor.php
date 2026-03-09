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
            'FILL_EMPTY_NINE' => new FillemptyNineConsigne(),
            'FILL_EMPTY_NR' => new FillemptyNrConsigne(),
            'UPPERCASE_NO_ACCENTS_WITH_PUNCTUATION' => new UppercaseNoAccentConsigne(),
            'PREFIX_AFA' => new PrefixAfaConsigne(),
            'PREFIX_ASSOCIATION_FA' => new PrefixAssociationFaConsigne(),
            'DATE_JJMM' => new DateDayMonthConsigne(),
            'DATE_AAAA_MM_JJ' => new DateYearMonthDayConsigne(),
            'DATE_JJMMAAA' => new DateDayMonthYearConsigne(),
            'DATE_YEAR' => new DateYearConsigne(),
            'DATE_STANDARD' => new DateStandardConsigne(),
            'DATE_EMPTY' => new DateEmpyConsigne(),
            'DATE_SECTION' => new DateSectionConsigne(),
            'TO_NUMERIC' => new ToNumericConsigne(),
            'LEADING_ZERO' => new LeadingZeroConsigne(),
            'CONCATENER_ADRESSE' => new AdresseConcateConsigne(),
            'ID_SATISFACTION' => new IdSatisfactionConsigne(),
            'ID_SOCIAL' => new IdSocialConsigne(),
            'ID_STATUT' => new IdStatutConsigne(),
            'UP_VALUE' => new UpValueConsigne(),
            'DOWN_VALUE' => new DownValueConsigne(),
            default => throw new \Exception("Consigne inconnue : $code")
        };
    }
}

