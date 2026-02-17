<?php
namespace App\Services;

use App\Services\Consignes\AdresseConcateConsigne;
use App\Services\Consignes\DateDayMonthConsigne;
use App\Services\Consignes\DateDayMonthYearConsigne;
use App\Services\Consignes\DateEmptyConsigne;
use App\Services\Consignes\DateSectionConsigne;
use App\Services\Consignes\DateStandardConsigne;
use App\Services\Consignes\DateYearConsigne;
use App\Services\Consignes\DateYearMonthDayConsigne;
use App\Services\Consignes\Date§Consigne;
use App\Services\Consignes\DownValueConsigne;
use App\Services\Consignes\FillemptyConsigne;
use App\Services\Consignes\FillemptyNineConsigne;
use App\Services\Consignes\FillemptyNrConsigne;
use App\Services\Consignes\IdSatisfactionConsigne;
use App\Services\Consignes\IdSocialConsigne;
use App\Services\Consignes\IdStatutConsigne;
use App\Services\Consignes\LeadingZeroConsigne;
use App\Services\Consignes\LowercaseConsigne;
use App\Services\Consignes\PrefixAfaConsigne;
use App\Services\Consignes\PrefixAssociationFaConsigne;
use App\Services\Consignes\ToNumericConsigne;
use App\Services\Consignes\UppercaseConsigne;
use App\Services\Consignes\CompacterConsigne;
use App\Services\Consignes\ToExtractConsigne;
use App\Services\Consignes\UppercaseNoAccentConsigne;
use App\Services\Consignes\UpValueConsigne;


class ConsigneExecutor
{
    public function getHandler(string $code)
    {
        return match ($code) {
            'LOWERCASE' => new LowercaseConsigne(),
            'UPPERCASE' => new UppercaseConsigne(),
            'UPPERCASE_NO_ACCENTS_WITH_PUNCTUATION' => new UppercaseNoAccentConsigne(),
            'FILL_EMPTY' => new FillemptyConsigne(),
            'FILL_EMPTY_NINE' => new FillemptyNineConsigne(),
            'FILL_EMPTY_NR' => new FillemptyNrConsigne(),
            'COMPACTER' => new CompacterConsigne(),
            'TO_EXTRACT' => new ToExtractConsigne(),
            'LEADING_ZERO' => new LeadingZeroConsigne(),
            'TO_NUMERIC' => new ToNumericConsigne(),
            'UP_VALUE' => new UpValueConsigne(),
            'DOWN_VALUE' => new DownValueConsigne(),
            'ID_SOCIAL' => new IdSocialConsigne(),
            'ID_STATUT' => new IdStatutConsigne(),
            'ID_SATISFACTION' => new IdSatisfactionConsigne(),
            'PREFIX_AFA' => new PrefixAfaConsigne(),
            'PREFIX_ASSOCIATION_FA' => new PrefixAssociationFaConsigne(),
            'DATE_STANDARD' => new DateStandardConsigne(),
            'DATE_AAAA_MM_JJ' => new DateYearMonthDayConsigne(),
            'DATE_JJMM' => new DateDayMonthConsigne(),
            'DATE_YEAR' => new DateYearConsigne(),
            'DATE_JJMMAAA' => new DateDayMonthYearConsigne(),
            'DATE_EMPTY' => new DateEmptyConsigne(),
            'DATE_SECTION' => new DateSectionConsigne(),
            'CONCATENER_ADRESSE' => new AdresseConcateConsigne(),


            default => throw new \Exception("Consigne inconnue : $code")
        };
    }
}

