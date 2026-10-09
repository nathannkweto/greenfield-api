<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Securely stream or download an attached asset.
     */
    public function download(Request $request, string $public_id): StreamedResponse
    {
        /** @var File $file */
        $file = File::query()->where('public_id', $public_id)->firstOrFail();

        $disk = $file->disk;

        // Check assigned disk, then fall back between 'public' and 'local' storage locations
        if (!Storage::disk($disk)->exists($file->path)) {
            if ($disk !== 'public' && Storage::disk('public')->exists($file->path)) {
                $disk = 'public';
            } elseif ($disk !== 'local' && Storage::disk('local')->exists($file->path)) {
                $disk = 'local';
            } else {
                abort(404, 'File asset not found on storage disk.');
            }
        }

        return Storage::disk($disk)->response(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
            ]
        );
    }
}
