<?php
namespace App\Services;

use App\Services\Consignes\AdresseConcateConsigne;
use App\Services\Consignes\AjoutSeparateurConsigne;
use App\Services\Consignes\CompacterConsigne;
use App\Services\Consignes\ConcatenationConsigne;
use App\Services\Consignes\DateDayMonthConsigne;
use App\Services\Consignes\DateDayMonthYearConsigne;
use App\Services\Consignes\DateEmptyConsigne;
use App\Services\Consignes\DateSectionConsigne;
use App\Services\Consignes\DateStandardConsigne;
use App\Services\Consignes\DateYearConsigne;
use App\Services\Consignes\DateYearMonthDayConsigne;
use App\Services\Consignes\DownValueConsigne;
use App\Services\Consignes\FillemptyConsigne;
use App\Services\Consignes\FilleemptyDynConsigne;
use App\Services\Consignes\FillemptyNineConsigne;
use App\Services\Consignes\FillemptyNrConsigne;


use App\Services\Consignes\FillIfNotEmptyConsigne;
use App\Services\Consignes\UppercaseNoAccentConsigne;
use App\Services\Consignes\PrefixAfaConsigne;
use App\Services\Consignes\PrefixAssociationFaConsigne;


use App\Services\Consignes\IdSatisfactionConsigne;
use App\Services\Consignes\IdSocialConsigne;
use App\Services\Consignes\IdStatutConsigne;
use App\Services\Consignes\LeadingZeroConsigne;
use App\Services\Consignes\LowercaseConsigne;

use App\Services\Consignes\PrefixConsigne;
use App\Services\Consignes\SiAutreChampRenseigneConsigne;
use App\Services\Consignes\SiVideAlorsConsigne;

use App\Services\Consignes\UppercaseConsigne;
use App\Services\Consignes\UpValueConsigne;
use App\Services\Consignes\ToNumericConsigne;
use App\Services\Consignes\EmptyAlwaysConsigne;


use App\Services\Consignes\ExtraireNomLotConsigne;
use App\Services\Consignes\IncrementationConsigne;
use App\Services\Consignes\ConcatenerChampsConsigne;
use App\Services\Consignes\ExtraireDonneesImageConsigne;

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
            'DATE_EMPTY' => new DateEmptyConsigne(),
            'DATE_SECTION' => new DateSectionConsigne(),
            'TO_NUMERIC' => new ToNumericConsigne(),
            'LEADING_ZERO' => new LeadingZeroConsigne(),
            'CONCATENER_ADRESSE' => new AdresseConcateConsigne(),
            'ID_SATISFACTION' => new IdSatisfactionConsigne(),
            'ID_SOCIAL' => new IdSocialConsigne(),
            'ID_STATUT' => new IdStatutConsigne(),
            'UP_VALUE' => new UpValueConsigne(),
            'DOWN_VALUE' => new DownValueConsigne(),
            'EXTRAIRE_NOM_LOT' => new ExtraireNomLotConsigne(),
            'INCREMENT' => new IncrementationConsigne(),
            'CONCATENER_CHAMPS' => new ConcatenerChampsConsigne(),
            'EMPTYALWAYS' => new EmptyAlwaysConsigne(),

            'AJOUT_SEPARATEUR' => new AjoutSeparateurConsigne(),

            'FILL_EMPTY_DYN' => new FilleemptyDynConsigne(),
            'EMPTY_PUT_VALUE' => new FillIfNotEmptyConsigne(),
            'EXTRAIRE_NOM_IMAGE' => new ExtraireDonneesImageConsigne(),
            'PAS_CONSIGNE_SPECIFIQUE' => new PasSpecifiquesConsigne(),

            default => throw new \Exception("Consigne inconnue : $code")
        };
    }
}

