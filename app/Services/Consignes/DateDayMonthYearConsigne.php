<?php

namespace App\Services\Consignes;

use Carbon\Carbon;

class DateDayMonthYearConsigne implements ConsigneInterface
{
    public function appliquer(array $ligne, array $champs, array $parametres = []): array
    {
        foreach ($champs as $champ) {

            if (!isset($ligne[$champ]) || empty($ligne[$champ])) {
                continue;
            }

            $valeur = trim($ligne[$champ]);

            // Nettoyage : suppression des espaces
            $valeur = str_replace(' ', '', $valeur);

            $date = null;

            try {
                // Cas 1 : 8 chiffres consécutifs JJMMAAAA
                if (preg_match('/^\d{8}$/', $valeur)) {
                    $date = Carbon::createFromFormat('dmY', $valeur);

                // Cas 2 : 6 chiffres consécutifs JJMMYY
                } elseif (preg_match('/^\d{6}$/', $valeur)) {
                    $date = Carbon::createFromFormat('dmy', $valeur);

                // Cas 3 : séparateurs "/" ou "-"
                } else {
                    $formats = [
                        'd/m/Y',
                        'd-m-Y',
                        'Y-m-d',
                        'Y/m/d',
                        'd/m/y',
                        'd-m-y',
                    ];

                    foreach ($formats as $format) {
                        try {
                            $date = Carbon::createFromFormat($format, $valeur);
                            if ($date !== false) {
                                break;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                // Si date valide
                if ($date) {
                    $ligne[$champ] = $date->format('d/m/Y'); // JJ/MM/AAAA
                } else {
                    $ligne[$champ] = null; // ou garder la valeur originale
                }

            } catch (\Exception $e) {
                $ligne[$champ] = null;
            }
        }

        return $ligne;
    }
}