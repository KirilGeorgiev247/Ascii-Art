<?php

namespace App\service\draw;

class ColorChangeService
{

    public static function changeColor(&$canvas, $fromColor, $toColor)
    {
        foreach ($canvas as $y => $row) {
            foreach ($row as $x => $color) {
                if ($color === $fromColor) {
                    $canvas[$y][$x] = $toColor;
                }
            }
        }
    }
}