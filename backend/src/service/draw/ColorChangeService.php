<?php

namespace App\service\draw;

class ColorChangeService
{

    // Change all pixels of one color to another
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