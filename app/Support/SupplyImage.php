<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Stores the photo of an IT supply item.
 *
 * Whatever is uploaded is decoded and re-encoded as a clean JPEG (this also drops EXIF/GPS
 * data and anything hidden in the file), fixed for phone-camera rotation, and shrunk:
 *   supplies/<uuid>.jpg         up to 1000px — the item page and the enlarge view
 *   supplies/<uuid>_thumb.jpg   up to 360px  — lists, dialogs and tickets (fast to load)
 * Each upload gets a new random name, so a replaced photo is never served from a stale cache.
 */
class SupplyImage
{
    public const DISK = 'public';

    public const MAIN_SIZE = 1000;

    public const THUMB_SIZE = 360;

    /** Refuse absurd dimensions before decoding — a decoded image costs ~4 bytes per pixel. */
    private const MAX_PIXELS = 50_000_000;

    /**
     * @return string the path of the main image (the thumbnail's path is derived from it)
     *
     * @throws InvalidArgumentException when the file isn't a picture we can use
     */
    public static function store(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $info = $path ? @getimagesize($path) : false;

        if (! $info) {
            throw new InvalidArgumentException('That file is not a picture we can read. Please use a JPG, PNG or WebP photo.');
        }

        [$width, $height, $type] = $info;

        if ($width * $height > self::MAX_PIXELS) {
            throw new InvalidArgumentException('That picture is too large. Please use a smaller photo.');
        }

        // Decide by what the file really is, not by what its name or the browser claims.
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if (! $image) {
            throw new InvalidArgumentException('Please use a JPG, PNG or WebP photo.');
        }

        if ($type === IMAGETYPE_JPEG) {
            $image = self::upright($image, $path);
        }

        $name = 'supplies/'.Str::uuid();
        $disk = Storage::disk(self::DISK);

        $disk->put("{$name}.jpg", self::encode($image, self::MAIN_SIZE));
        $disk->put("{$name}_thumb.jpg", self::encode($image, self::THUMB_SIZE));

        return "{$name}.jpg";
    }

    /** Remove an item's photo and its thumbnail. Safe to call with null. */
    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk(self::DISK)->delete([$path, self::thumbPath($path)]);
    }

    public static function thumbPath(string $path): string
    {
        return preg_replace('/\.jpg$/', '_thumb.jpg', $path);
    }

    /** Phone cameras store sideways pictures with a rotation flag; apply it so they look right. */
    private static function upright(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle !== 0 && ($rotated = imagerotate($image, $angle, 0))) {
            return $rotated;
        }

        return $image;
    }

    /** Scale down to fit $max px (never up), flatten on white, and return JPEG bytes. */
    private static function encode(\GdImage $source, int $max): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $max / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255)); // transparent PNGs land on white
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 85);

        return (string) ob_get_clean();
    }
}
