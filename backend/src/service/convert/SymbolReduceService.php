<?php

namespace App\service\convert;

class SymbolReduceService
{
    public static function convert($img, $symbols = '@# .')
    {
        $width = imagesx($img);
        $height = imagesy($img);
        $ascii = '';
        $chars = str_split($symbols);

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = (int)(($r + $g + $b) / 3);
                $idx = (int)($gray / 255 * (count($chars)-1));
                $ascii .= $chars[$idx];
            }
            $ascii .= "\n";
        }
        return $ascii;
    }
}