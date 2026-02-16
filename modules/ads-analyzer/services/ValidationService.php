<?php

namespace Modules\AdsAnalyzer\Services;

class ValidationService
{
    /**
     * Valida dati progetto
     */
    public static function validateProject(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Il nome del progetto e obbligatorio';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'Il nome non puo superare 255 caratteri';
        }

        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'La descrizione non puo superare 1000 caratteri';
        }

        return $errors;
    }

    /**
     * Valida contesto business
     */
    public static function validateBusinessContext(string $context): array
    {
        $errors = [];

        if (empty($context)) {
            $errors['business_context'] = 'Il contesto business e obbligatorio';
        } elseif (strlen($context) < 30) {
            $errors['business_context'] = 'Il contesto deve essere almeno 30 caratteri';
        } elseif (strlen($context) > 5000) {
            $errors['business_context'] = 'Il contesto non puo superare 5000 caratteri';
        }

        return $errors;
    }

    /**
     * Valida file CSV upload
     */
    public static function validateCsvUpload(array $file): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['csv_file'] = 'Errore durante l\'upload del file';
            return $errors;
        }

        // Verifica tipo MIME
        $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // CSV a volte viene rilevato come text/plain, verifichiamo anche estensione
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($mimeType, $allowedMimes) && $extension !== 'csv') {
            $errors['csv_file'] = 'Il file deve essere in formato CSV';
            return $errors;
        }

        // Verifica dimensione (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            $errors['csv_file'] = 'Il file non puo superare 10MB';
            return $errors;
        }

        // Verifica dimensione minima
        if ($file['size'] < 100) {
            $errors['csv_file'] = 'Il file sembra vuoto o troppo piccolo';
            return $errors;
        }

        return $errors;
    }

    /**
     * Valida dati Campaign Creator
     */
    public static function validateCampaignCreator(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Il nome del progetto e obbligatorio';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'Il nome non puo superare 255 caratteri';
        }

        if (empty($data['campaign_type_gads']) || !in_array($data['campaign_type_gads'], ['search', 'pmax'])) {
            $errors['campaign_type_gads'] = 'Seleziona il tipo di campagna (Search o PMax)';
        }

        $inputMode = $data['input_mode'] ?? 'url';
        if (!in_array($inputMode, ['url', 'brief', 'both'])) {
            $inputMode = 'url';
        }

        // URL: richiesto se mode e 'url' o 'both'
        if ($inputMode !== 'brief') {
            if (empty($data['landing_url'])) {
                $errors['landing_url'] = 'L\'URL della landing page e obbligatorio';
            } elseif (!filter_var($data['landing_url'], FILTER_VALIDATE_URL)) {
                $errors['landing_url'] = 'L\'URL inserito non e valido';
            }
        }

        // Brief: richiesto se mode e 'brief' o 'both'
        if ($inputMode !== 'url') {
            if (empty($data['brief']) || strlen(trim($data['brief'])) < 20) {
                $errors['brief'] = 'Il brief deve essere almeno 20 caratteri';
            } elseif (strlen($data['brief']) > 5000) {
                $errors['brief'] = 'Il brief non puo superare 5000 caratteri';
            }
        }

        return $errors;
    }

    /**
     * Valida nome contesto salvato
     */
    public static function validateSavedContext(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Il nome del contesto e obbligatorio';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'Il nome non puo superare 255 caratteri';
        }

        if (empty($data['context'])) {
            $errors['context'] = 'Il contenuto del contesto e obbligatorio';
        } elseif (strlen($data['context']) < 30) {
            $errors['context'] = 'Il contesto deve essere almeno 30 caratteri';
        }

        return $errors;
    }
}
