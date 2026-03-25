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

    public function array(): array
    {
        $data = [];
        $compteur = 1;

        foreach ($this->data as $ligne) {

            $ligne = (array) $ligne;

            // 🔹 supprimer colonnes inutiles
            unset($ligne['created_at'], $ligne['updated_at']);

            // 🔹 N_ENR
            $ligne['n_enr'] = str_pad($compteur, 4, '0', STR_PAD_LEFT);

            // 🔹 logique STEFI
            if (strtoupper(trim($this->dossier)) === 'STEFI MEDIAMETRIE') {

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
    if (empty($this->data)) {
        return [];
    }

    // 🔥 supprimer les colonnes inutiles des en-têtes
    $headings = array_keys($this->data[0]);

    $headings = array_filter($headings, function ($col) {
        return !in_array($col, ['created_at', 'updated_at']);
    });

    return array_map(function ($heading) {
        return strtoupper($heading);
    }, $headings);
}



    // ✅ Mettre la première ligne en GRAS
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // ligne 1 en gras
        ];
    }

}
