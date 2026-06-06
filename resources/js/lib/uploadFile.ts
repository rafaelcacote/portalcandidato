/**
 * Ensures multipart uploads use a valid filename.
 * Some browsers (and Inertia transform + FormData) send the literal name "undefined".
 */
export function normalizeUploadFile(file: File, fallbackBase = 'upload'): File {
    const name = file.name?.trim();

    if (name && name !== 'undefined' && name !== 'null') {
        return file;
    }

    const extensionFromMime: Record<string, string> = {
        'image/jpeg': 'jpg',
        'image/jpg': 'jpg',
        'image/png': 'png',
        'image/webp': 'webp',
        'image/gif': 'gif',
    };

    const ext =
        extensionFromMime[file.type] ??
        (file.type.startsWith('image/')
            ? (file.type.split('/')[1] ?? 'jpg')
            : 'jpg');

    return new File([file], `${fallbackBase}.${ext}`, {
        type: file.type || 'image/jpeg',
    });
}
