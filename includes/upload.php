<?php

function store_safe_upload(array $file, string $folder): string
{
    $allowedFolders = ['assignments', 'study_materials', 'submissions'];
    if (!in_array($folder, $allowedFolders, true)) {
        throw new InvalidArgumentException('The upload destination is not allowed.');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('Files must be 10 MB or smaller.');
    }

    $temporaryPath = $file['tmp_name'] ?? '';
    if (!is_string($temporaryPath) || $temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The uploaded file could not be verified.');
    }

    $extensions = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/CDFV2'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/CDFV2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'zip' => ['application/zip', 'application/x-zip'],
    ];
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    if (!isset($extensions[$extension]) || !in_array($mime, $extensions[$extension], true)) {
        throw new RuntimeException('Unsupported file type. Use PDF, Office documents, images, or ZIP files.');
    }

    $directory = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The upload directory is unavailable.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
        throw new RuntimeException('The uploaded file could not be saved.');
    }

    return 'uploads/' . $folder . '/' . $filename;
}

function store_profile_photo(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The photo upload failed. Please try again.');
    }
    if (!isset($file['size']) || !is_int($file['size']) || $file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Profile photos must be 5 MB or smaller.');
    }

    $temporaryPath = $file['tmp_name'] ?? '';
    if (!is_string($temporaryPath) || $temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The uploaded photo could not be verified.');
    }

    $image = @getimagesize($temporaryPath);
    if (!is_array($image)
        || !isset($image[0], $image[1], $image[2])
        || $image[0] < 1
        || $image[1] < 1
        || $image[0] > 6000
        || $image[1] > 6000
        || $image[0] * $image[1] > 12000000) {
        throw new RuntimeException('Use a valid square-croppable JPG or PNG image.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $imageTypes = [
        IMAGETYPE_JPEG => ['image/jpeg', 'imagecreatefromjpeg'],
        IMAGETYPE_PNG => ['image/png', 'imagecreatefrompng'],
    ];
    $type = (int) $image[2];
    if (!isset($imageTypes[$type])
        || $mime !== $imageTypes[$type][0]
        || !function_exists($imageTypes[$type][1])) {
        throw new RuntimeException('Use a valid JPG or PNG profile photo.');
    }

    $source = @call_user_func($imageTypes[$type][1], $temporaryPath);
    if (!$source) {
        throw new RuntimeException('The profile photo could not be processed.');
    }

    $size = 400;
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $cropSize = min($sourceWidth, $sourceHeight);
    $sourceX = (int) floor(($sourceWidth - $cropSize) / 2);
    $sourceY = (int) floor(($sourceHeight - $cropSize) / 2);
    $cropped = imagecreatetruecolor($size, $size);
    if (!$cropped) {
        imagedestroy($source);
        throw new RuntimeException('The profile photo could not be processed.');
    }

    $background = imagecolorallocate($cropped, 255, 255, 255);
    imagefill($cropped, 0, 0, $background);
    if (!imagecopyresampled($cropped, $source, 0, 0, $sourceX, $sourceY, $size, $size, $cropSize, $cropSize)) {
        imagedestroy($source);
        imagedestroy($cropped);
        throw new RuntimeException('The profile photo could not be processed.');
    }
    imagedestroy($source);

    $directory = __DIR__ . '/../uploads/profile_photos';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        imagedestroy($cropped);
        throw new RuntimeException('The profile photo directory is unavailable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.jpg';
    $absolutePath = $directory . '/' . $filename;
    $saved = @imagejpeg($cropped, $absolutePath, 86);
    imagedestroy($cropped);
    if (!$saved) {
        if (is_file($absolutePath) && !@unlink($absolutePath)) {
            error_log('A partial profile photo could not be removed.');
        }
        throw new RuntimeException('The profile photo could not be saved.');
    }

    return 'uploads/profile_photos/' . $filename;
}

function delete_profile_photo(?string $path): void
{
    if ($path === null || !preg_match('#\Auploads/profile_photos/[a-f0-9]{32}\.jpg\z#', $path)) {
        return;
    }

    $absolutePath = __DIR__ . '/../' . $path;
    if (is_file($absolutePath) && !unlink($absolutePath)) {
        throw new RuntimeException('The old profile photo could not be removed.');
    }
}
