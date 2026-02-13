<?php

namespace Core;

/**
 * BrandingHelper
 * Genera palette colori 50-950 da un singolo colore base hex
 */
class BrandingHelper
{
    /**
     * Genera palette completa (50-950) da un colore base hex.
     * Usa lo spazio colore HSL per creare varianti chiare e scure.
     *
     * @param string $hex Colore base in formato #RRGGBB
     * @return array Array associativo [50 => '#...', 100 => '#...', ..., 950 => '#...']
     */
    public static function generateShades(string $hex): array
    {
        // Validazione
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '#006e96'; // fallback al primary default
        }

        $hsl = self::hexToHsl($hex);
        $h = $hsl['h'];
        $s = $hsl['s'];
        $l = $hsl['l'];

        // Mappa sfumature: shade => [lightness, saturation_factor]
        // Le sfumature chiare (50-400) hanno lightness alta e saturazione ridotta
        // Le sfumature scure (600-950) hanno lightness ridotta
        $shadeMap = [
            50  => ['l' => 96, 's' => $s * 0.30],
            100 => ['l' => 91, 's' => $s * 0.40],
            200 => ['l' => 82, 's' => $s * 0.55],
            300 => ['l' => 68, 's' => $s * 0.70],
            400 => ['l' => 52, 's' => $s * 0.85],
            500 => ['l' => $l, 's' => $s],
            600 => ['l' => max(5, $l * 0.82), 's' => $s],
            700 => ['l' => max(5, $l * 0.65), 's' => $s],
            800 => ['l' => max(4, $l * 0.50), 's' => $s],
            900 => ['l' => max(3, $l * 0.35), 's' => $s],
            950 => ['l' => max(2, $l * 0.20), 's' => $s],
        ];

        $result = [];
        foreach ($shadeMap as $shade => $config) {
            $result[$shade] = self::hslToHex($h, $config['s'], $config['l']);
        }

        return $result;
    }

    /**
     * Converte hex (#RRGGBB) in HSL [h, s, l]
     */
    private static function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                case $b:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
            }
        }

        return [
            'h' => round($h * 360, 2),
            's' => round($s * 100, 2),
            'l' => round($l * 100, 2),
        ];
    }

    /**
     * Converte HSL in hex (#RRGGBB)
     */
    private static function hslToHex(float $h, float $s, float $l): string
    {
        $h = $h / 360;
        $s = $s / 100;
        $l = $l / 100;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = self::hueToRgb($p, $q, $h + 1 / 3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1 / 3);
        }

        return sprintf('#%02x%02x%02x',
            (int) round($r * 255),
            (int) round($g * 255),
            (int) round($b * 255)
        );
    }

    /**
     * Helper per conversione hue → RGB
     */
    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }

    /**
     * Lista font Google Fonts consentiti
     */
    public static function getAllowedFonts(): array
    {
        return [
            'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat',
            'Poppins', 'Source Sans 3', 'Nunito', 'Raleway',
            'Work Sans', 'DM Sans', 'Plus Jakarta Sans',
        ];
    }
}
