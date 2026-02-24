<?php

namespace App\Services;

class EncodingService
{
    /**
     * Encode récursivement un tableau ou une valeur en UTF-8
     *
     * @param mixed $data
     * @return mixed
     */
    public function utf8EncodeRecursive($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->utf8EncodeRecursive($value);
            }
            return $data;
        }

        if (is_string($data)) {
            // Vérifie si déjà UTF-8
            if (!mb_check_encoding($data, 'UTF-8')) {
                return mb_convert_encoding($data, 'UTF-8', ['Windows-1252', 'ISO-8859-1', 'UTF-8']);
            }
            return $data;
        }

        return $data;
    }
}
