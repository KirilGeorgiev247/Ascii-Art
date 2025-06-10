<?php

namespace App\service\convert;

class ColorReduceService
{
    public static function convert($img, $symbols = '@%#*+=-:. ', $colors = 8)
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
                $gray = (int) (($r + $g + $b) / 3);
                $reduced = (int) ($gray / (256 / $colors));
                $idx = (int) ($reduced / ($colors - 1) * (count($chars) - 1));
                $ascii .= $chars[$idx];
            }
            $ascii .= "\n";
        }
        return $ascii;
    }
}