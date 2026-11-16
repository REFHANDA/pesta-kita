<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * BE-3: fixture unggahan nyata tanpa ekstensi GD.
 * UploadedFile::fake()->image() butuh GD (tidak tersedia di lingkungan
 * ini), maka dipakai biner minimum valid yang lolos finfo/getimagesize.
 */
trait MakesUploadedFiles
{
    /**
     * Gambar 1x1 valid sesuai ekstensi (jpg/jpeg/png/webp).
     */
    protected function imageFile(string $name = 'foto.jpg'): UploadedFile
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $binary = match ($extension) {
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='),
            'webp' => base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'),
            default => base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q=='),
        };

        return UploadedFile::fake()->createWithContent($name, $binary);
    }

    /**
     * PDF minimum valid; $kilobytes menambah isi agar bisa menguji batas 5 MB.
     */
    protected function pdfFile(string $name = 'dokumen.pdf', int $kilobytes = 1): UploadedFile
    {
        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $content .= str_repeat('x', max(0, $kilobytes * 1024 - strlen($content)));

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
