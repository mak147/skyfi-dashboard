<?php declare(strict_types=1);
namespace SkyFi\System\Services;
final class BrandingAssetService
{
    public function store(array $file, string $type): string
    {
        $name = basename((string)($file['name'] ?? 'asset'));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: 'bin');
        $safeType = preg_replace('/[^a-z_]/','',$type) ?: 'asset';
        $dir = dirname(__DIR__, 3) . '/storage/uploads/branding';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $path = $dir . '/' . $safeType . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (is_uploaded_file((string)$file['tmp_name'])) move_uploaded_file((string)$file['tmp_name'], $path); else copy((string)$file['tmp_name'], $path);
        return 'storage/uploads/branding/' . basename($path);
    }
}
