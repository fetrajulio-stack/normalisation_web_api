<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NormalisationExport implements FromArray, WithHeadings
    {
        protected $data;

        public function __construct(array $data)
        {
        $this->data = $data;
        }

        public function array(): array
        {
        return $this->data;
        }

        public function headings(): array
        {
            if (empty($this->data)) {
                return [];
            }

            return array_map(function ($heading) {
                return strtoupper($heading);
            }, array_keys($this->data[0]));
        }

        // ✅ Mettre la première ligne en GRAS
        public function styles(Worksheet $sheet)
        {
            return [
                1 => ['font' => ['bold' => true]], // ligne 1 en gras
            ];
        }

    }
