<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;


class MappingImport implements ToCollection, WithHeadingRow
{
    // On crée une propriété pour stocker les données
    private $data;

    public function collection(Collection $rows)
    {
        // On remplit la propriété avec les lignes du fichier
        $this->data = $rows;
    }

    // On crée une fonction pour récupérer ces données depuis le contrôleur
    public function getImportedData()
    {
        return $this->data;
    }
}
