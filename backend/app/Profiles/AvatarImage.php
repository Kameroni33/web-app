<?php

namespace App\Profiles;

use Illuminate\Validation\ValidationException;

/**
 * Turns an uploaded photo into a small square WebP. Re-encoding drops
 * everything but the pixels (EXIF, GPS location, embedded payloads).
 */
class AvatarImage
{
    public const SIZE = 256;

    public const MIME_TYPE = 'image/webp';

    public function fromFile(string $path): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            throw ValidationException::withMessages(['image' => "That file isn't a photo we can read."]);
        }

        // Centre square crop, scaled down to SIZE (never up).
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $target = min($side, self::SIZE);

        $avatar = imagecreatetruecolor($target, $target);
        imagealphablending($avatar, false);
        imagesavealpha($avatar, true);
        imagecopyresampled(
            $avatar, $source,
            0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            $target, $target, $side, $side,
        );

        ob_start();
        imagewebp($avatar, null, 85);

        return (string) ob_get_clean();
    }
}
