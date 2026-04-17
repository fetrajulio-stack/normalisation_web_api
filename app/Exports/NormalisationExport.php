<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NormalisationExport implements FromArray, WithHeadings
{
    protected $data;
    protected $dossier;
    protected $processedData = [];

    public function __construct(array $data, string $dossier = '')
    {
        $this->data = $data;
        $this->dossier = strtoupper($dossier);
    }

    private function isStefi(): bool
    {
        return trim($this->dossier) === 'STEFI MEDIAMETRIE';
    }

    private function stefiColumnsToRemove(): array
    {
        return [
            'QUESTIONNAIRE',
            'PARIS_1_IVRY_2_LILLE_LOMME_3',
            'SEANCE_4_SEANCES'
        ];
    }

    public function array(): array
    {
        $data = [];
        $compteur = 1;

        foreach ($this->data as $ligne) {
            $ligne = (array) $ligne;

            // 🔹 Garder tes suppressions
            unset($ligne['created_at'], $ligne['updated_at']);

            if ($this->isStefi()) {
                $columnsToRemove = array_map('strtolower', $this->stefiColumnsToRemove());
                foreach ($ligne as $key => $value) {
                    if (in_array(strtolower($key), $columnsToRemove, true)) {
                        unset($ligne[$key]);
                    }
                }
            }

            // 🔹 LA SEULE DIFFÉRENCE : On cherche la clé avant d'écrire
            $valeurEnr = str_pad($compteur, 4, '0', STR_PAD_LEFT);
            $cleEnrTrouvee = false;

            foreach ($ligne as $key => $value) {
                // Si la clé est N_ENR (ou son nom mappé comme PATATE)
                if (strtoupper($key) === 'N_ENR' || strtoupper($key) === 'PATATE') {
                    $ligne[$key] = $valeurEnr; // On écrase la valeur au même endroit
                    $cleEnrTrouvee = true;
                    break;
                }
            }

            // Si vraiment aucune colonne N_ENR n'existe, on la crée (comportement par défaut)
            if (!$cleEnrTrouvee) {
                $ligne['N_ENR'] = $valeurEnr;
            }

            // 🔹 Garder ta logique STEFI intacte
            if ($this->isStefi()) {
                $nLot = '';
                foreach($ligne as $k => $v) {
                    if(strtoupper($k) === 'N_LOT') { $nLot = $v; break; }
                }

                $ville = '';
                if (stripos($nLot, 'PARIS') !== false) {
                    $ville = 1;
                } elseif (stripos($nLot, 'IVRY') !== false) {
                    $ville = 2;
                } elseif (stripos($nLot, 'LILLE') !== false || stripos($nLot, 'LOMME') !== false) {
                    $ville = 3;
                }

                $seance = '';
                if (preg_match('/_S(\d+)_/i', $nLot, $matches)) {
                    $seance = $matches[1];
                }

                $ligne['ville']  = $ville;
                $ligne['seance'] = $seance;
            }

            $data[] = $ligne;
            $compteur++;
        }

        $this->processedData = $data;
        return $data;
    }

    public function headings(): array
    {

        if (empty($this->processedData)) {
            $this->array();
        }

        if (empty($this->processedData)) {
            return [];
        }


        $headings = array_keys($this->processedData[0]);

        return array_map(function ($heading) {

            return strtoupper($heading);
        }, $headings);
    }


    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
