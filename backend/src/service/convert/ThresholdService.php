<?php

namespace App\service\convert;

class ThresholdService
{
    public static function convert($img, $symbols = '@ ', $threshold = 128)
    {
        $width = imagesx($img);
        $height = imagesy($img);
        $ascii = '';
        $chars = str_split($symbols);

        if (count($chars) < 2) {
            $chars = ['@', ' '];
        }

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $gray = (int) ($r * 0.3 + $g * 0.59 + $b * 0.11);

                $ascii .= $gray > $threshold ? $chars[1] : $chars[0];
            }
            $ascii .= "\n";
        }

        return $ascii;
    }
}