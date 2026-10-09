<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\File;
use App\Models\File as FileModel;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\FilesApiInterface;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\FileUploadResponse;
use OpenAPI\Server\Model\FileUploadResponseData;
use OpenAPI\Server\Model\StudentFileItem;
use OpenAPI\Server\Model\StudentFilesResponse;
use OpenAPI\Server\Model\ValidationErrorResponse;

class FileApiService implements FilesApiInterface
{
    /**
     * Store uploaded file binary and write metadata to database.
     *
     * @param UploadedFile $file
     * @param string|null $collection
     * @return FileUploadResponse|ValidationErrorResponse
     */
    public function filesUploadPost(
        UploadedFile $file,
        ?string $collection = null
    ): FileUploadResponse|ValidationErrorResponse {
        $publicId = Str::uuid()->toString();
        $disk = (string) config('filesystems.default', 's3');

        // Store file with a sanitized path structure
        $path = $file->storeAs(
            'documents/' . date('Y/m'),
            sprintf('%s.%s', $publicId, $file->getClientOriginalExtension()),
            $disk
        );

        // Persist file metadata
        $fileModel = new FileModel();
        $fileModel->public_id = $publicId;
        $fileModel->disk = $disk;
        $fileModel->path = $path;
        $fileModel->original_name = $file->getClientOriginalName();
        $fileModel->mime_type = $file->getClientMimeType();
        $fileModel->size = $file->getSize();
        $fileModel->uploaded_by_id = Auth::id();
        $fileModel->save();

        // Construct OpenAPI typed response DTO
        $responseData = new FileUploadResponseData(
            public_id: $fileModel->public_id,
            original_name: $fileModel->original_name,
            mime_type: $fileModel->mime_type,
            size: $fileModel->size
        );

        return new FileUploadResponse(
            status: 'success',
            message: 'File uploaded successfully.',
            data: $responseData
        );
    }
    /**
     * Retrieve Student Files and Documents
     */
    public function studentsPublicIdFilesGet(
        string $public_id,
        ?string $collection = null
    ): StudentFilesResponse|ErrorResponse {
        /** @var Student|null $student */
        $student = Student::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$student) {
            return new ErrorResponse(
                message: 'Student record not found.',
                error: 'NOT_FOUND'
            );
        }

        $query = $student->files();

        if (!empty($collection)) {
            $query->wherePivot('collection', $collection);
        }

        $files = $query->get();

        $fileItems = $files->map(function (File $file) {
            return new StudentFileItem(
                id: $file->public_id,
                original_name: $file->original_name,
                mime_type: $file->mime_type,
                size: (int) $file->size,
                sort_order: (int) ($file->sort_order ?? 0),
                created_at: $file->created_at ? new \DateTime($file->created_at->toIso8601String()) : new \DateTime(),
                url: $file->url,
                collection: $file->collection
            );
        })->all();

        return new StudentFilesResponse(
            status: 'success',
            message: 'Student files retrieved successfully.',
            data: $fileItems
        );
    }
}
