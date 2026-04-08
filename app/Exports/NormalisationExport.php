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

            // 🔹 supprimer colonnes inutiles
            unset($ligne['created_at'], $ligne['updated_at']);

            // 🔹 suppression spécifique STEFI
            if ($this->isStefi()) {
                $columnsToRemove = array_map('strtolower', $this->stefiColumnsToRemove());
                foreach ($ligne as $key => $value) {
                    if (in_array(strtolower($key), $columnsToRemove, true)) {
                        unset($ligne[$key]);
                    }
                }
            }

            // 🔹 N_ENR
            $ligne['n_enr'] = str_pad($compteur, 4, '0', STR_PAD_LEFT);

            // 🔹 logique STEFI
            if ($this->isStefi()) {

                $nLot = $ligne['n_lot'] ?? '';

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

        // ✅ stocker les données nettoyées
        $this->processedData = $data;

        return $data;
    }

    public function headings(): array
    {
        // 🔥 sécurité : forcer le traitement si pas encore fait
        if (empty($this->processedData)) {
            $this->array();
        }

        if (empty($this->processedData)) {
            return [];
        }

        // 🔥 utiliser les colonnes réellement présentes
        $headings = array_keys($this->processedData[0]);

        return array_map(function ($heading) {
            return strtoupper($heading);
        }, $headings);
    }

    // ✅ Mettre la première ligne en GRAS
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}