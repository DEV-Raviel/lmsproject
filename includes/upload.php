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
