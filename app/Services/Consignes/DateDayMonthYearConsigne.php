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
            try {

                // Formats d'entrée possibles
                $formats = [
                    'd/m/Y',
                    'd-m-Y',
                    'Y-m-d',
                    'Y/m/d',
                    'dmY',
                    'd m Y',
                ];

                $date = null;

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

                if ($date) {
                    // ✅ Format final : JJ/MM/AAAA
                    $formatted = $date->format('d/m/Y');

                    // Forcer le texte pour Excel
                    // Ajout de l'apostrophe pour que Excel ne réinterprète pas
                    $ligne[$champ] = "'" . $formatted;

                    // ⚠️ Si vous utilisez PhpSpreadsheet, vous pouvez faire à la place :
                   // $sheet->setCellValueExplicit($cellCoordinate, $formatted, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $ligne[$champ] = null;
                }

            } catch (\Exception $e) {
                $ligne[$champ] = null;
            }
        }
        return $ligne;

    }
}