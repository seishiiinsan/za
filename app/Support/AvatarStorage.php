<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stockage des avatars.
 *
 * L'image est systématiquement réencodée : le fichier écrit sur disque ne
 * contient aucune métadonnée de l'original (EXIF, GPS, appareil, date de prise
 * de vue), qui permettrait de rapprocher les avatars de deux alters.
 */
class AvatarStorage
{
    public const MAX_SIDE = 512;

    public function store(UploadedFile $file, string $disk = 'public'): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new RuntimeException("Avatar illisible : l'image n'a pas pu être décodée.");
        }

        $resized = imagescale($image, ...$this->targetSize($image));
        imagedestroy($image);

        if ($resized === false) {
            throw new RuntimeException("Avatar illisible : l'image n'a pas pu être redimensionnée.");
        }

        ob_start();
        imagejpeg($resized, null, 85);
        $encoded = (string) ob_get_clean();
        imagedestroy($resized);

        $path = 'avatars/'.Str::uuid().'.jpg';
        Storage::disk($disk)->put($path, $encoded);

        return $path;
    }

    /**
     * @param  \GdImage  $image
     * @return array{0: int, 1: int}
     */
    protected function targetSize($image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, self::MAX_SIDE / max($width, $height));

        return [(int) round($width * $ratio), (int) round($height * $ratio)];
    }
}
