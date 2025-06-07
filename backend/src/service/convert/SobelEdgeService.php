<?php

namespace App\service\convert;

class SobelEdgeService
{
    public static function convert($img, $symbols = '@%#*+=-:. ')
    {
        $width = imagesx($img);
        $height = imagesy($img);
        $ascii = '';
        $chars = str_split($symbols);

        // Sobel kernels
        $gx = [[-1, 0, 1], [-2, 0, 2], [-1, 0, 1]];
        $gy = [[-1, -2, -1], [0, 0, 0], [1, 2, 1]];

        for ($y = 1; $y < $height - 1; $y += 2) {
            for ($x = 1; $x < $width - 1; $x++) {
                $sumX = $sumY = 0;

                for ($ky = -1; $ky <= 1; $ky++) {
                    for ($kx = -1; $kx <= 1; $kx++) {
                        $rgb = imagecolorat($img, $x + $kx, $y + $ky);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;

                        $gray = (int)($r * 0.3 + $g * 0.59 + $b * 0.11);

                        $sumX += $gx[$ky + 1][$kx + 1] * $gray;
                        $sumY += $gy[$ky + 1][$kx + 1] * $gray;
                    }
                }

                $magnitude = sqrt($sumX * $sumX + $sumY * $sumY);
                $magnitude = min(255, $magnitude);

                $index = (int)(($magnitude / 255) * (count($chars) - 1));
                $index = max(0, min($index, count($chars) - 1)); // protect against out-of-bounds

                $ascii .= $chars[$index];
            }
            $ascii .= "\n";
        }

        return $ascii;
    }
}