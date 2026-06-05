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
    protected $isIndexed = false;

    public function __construct(array $data, string $dossier = '', bool $isIndexed = false)
    {
        $this->data = $data;
        $this->dossier = strtoupper($dossier);
        $this->isIndexed = $isIndexed;
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
        $forceNima = false;

        foreach ($this->data as $ligne) {
            $ligne = (array) $ligne;

            if ($this->isIndexed ||
                isset($ligne['nom_fichier_indexe']) ||
                isset($ligne['NOM_FICHIER_INDEXE'])
            ) {
                $forceNima = true;
            }



            // 🔹 Garder tes suppressions
            unset($ligne['created_at'], $ligne['updated_at']);
            
            if (!$this->isIndexed) {
                unset(
                    $ligne['nom_fichier_indexe'],
                    $ligne['nom_fichier_index'],
                    $ligne['NOM_FICHIER_INDEXE']
                );
            }


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


            // Vérification N_IMA (sans créer de colonne)
            /*if ($this->isIndexed || $forceNima) {
                if (!array_key_exists('N_IMA', $ligne)) {
                    throw new \Exception("Colonne N_IMA manquante dans le fichier indexé");
                }
            }*/

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

            $mapped = array_map(function ($heading) {
            // Mapper nom_fichier_index → nom_fichier_indexe
            if (strtolower($heading) === 'nom_fichier_index') {
                return 'NOM_FICHIER_INDEXE';
            }
            return strtoupper($heading);
        }, $headings);

        // Pour les exports indexés, s'assurer que NOM_FICHIER_INDEXE est présent
        if ($this->isIndexed && !in_array('NOM_FICHIER_INDEXE', $mapped, true)) {
            $mapped[] = 'NOM_FICHIER_INDEXE';
        }

        return $mapped;
    }


    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
