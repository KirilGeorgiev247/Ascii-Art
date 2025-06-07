<?php

namespace App\service\draw;

class FloodFillService
{
    // Flood fill for 2D array
    public static function floodFill(&$canvas, $x, $y, $targetColor, $replacementColor)
    {
        $height = count($canvas);
        $width = count($canvas[0]);
        if ($targetColor === $replacementColor) return;

        $queue = [[$x, $y]];
        while ($queue) {
            [$cx, $cy] = array_pop($queue);
            if ($cx < 0 || $cy < 0 || $cx >= $width || $cy >= $height) continue;
            if ($canvas[$cy][$cx] !== $targetColor) continue;
            $canvas[$cy][$cx] = $replacementColor;
            $queue[] = [$cx + 1, $cy];
            $queue[] = [$cx - 1, $cy];
            $queue[] = [$cx, $cy + 1];
            $queue[] = [$cx, $cy - 1];
        }
    }

}