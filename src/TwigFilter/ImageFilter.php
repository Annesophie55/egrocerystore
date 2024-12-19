<?php
namespace App\TwigFilter;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ImageFilter extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('fix_image', [$this, 'fixImageName']),
        ];
    }

    public function fixImageName(?string $image): string
    {
        if (!$image) {
            return 'images/chocolate.jpg';
        }

        return rtrim($image, '.') . '.jpg';
    }
}
