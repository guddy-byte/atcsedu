<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $materials = Material::query()
            ->where('is_published', true)
            ->when(
                $request->filled('access_type'),
                fn ($query) => $query->where('access_type', $request->string('access_type')->toString())
            )
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();

                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('format', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $materials->getCollection()->map(fn (Material $material) => $this->formatMaterial($material, false))->all(),
                'meta' => [
                    'current_page' => $materials->currentPage(),
                    'per_page' => $materials->perPage(),
                    'total' => $materials->total(),
                    'last_page' => $materials->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Material $material): JsonResponse
    {
        abort_unless($material->is_published, 404);

        $hasAccess = $material->access_type === 'free'
            || $this->userHasMaterialAccess($this->resolveAuthenticatedUser($request), $material);

        return response()->json([
            'status' => 'success',
            'data' => [
                'material' => $this->formatMaterial($material, $hasAccess),
            ],
        ]);
    }

    public function view(Request $request, Material $material): StreamedResponse|JsonResponse
    {
        abort_unless($material->is_published, 404);

        $isFree = $material->access_type === 'free';
        $hasAccess = $isFree || $this->userHasMaterialAccess($this->resolveAuthenticatedUser($request), $material);

        if (! $hasAccess) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You need to purchase this material to view it.',
            ], 403);
        }

        $downloadUrl = $material->download_url;
        if (! $downloadUrl) {
            return response()->json(['status' => 'error', 'message' => 'No file attached to this material.'], 404);
        }

        $storageBaseUrl = rtrim(Storage::disk('public')->url(''), '/');

        if (str_starts_with($downloadUrl, $storageBaseUrl)) {
            $relativePath = ltrim(substr($downloadUrl, strlen($storageBaseUrl)), '/');
            if (! Storage::disk('public')->exists($relativePath)) {
                return response()->json(['status' => 'error', 'message' => 'File not found on server.'], 404);
            }
            return Storage::disk('public')->response($relativePath);
        }

        return redirect()->away($downloadUrl);
    }

    public function download(Request $request, Material $material): StreamedResponse|JsonResponse
    {
        abort_unless($material->is_published, 404);

        $isFree = $material->access_type === 'free';
        $hasAccess = $isFree || $this->userHasMaterialAccess($this->resolveAuthenticatedUser($request), $material);

        if (! $hasAccess) {
            return response()->json(['status' => 'error', 'message' => 'Purchase required to download this material.'], 403);
        }

        if (! $isFree) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paid materials are view-only and cannot be downloaded.',
            ], 403);
        }

        $downloadUrl = $material->download_url;

        if (! $downloadUrl) {
            return response()->json(['status' => 'error', 'message' => 'No file is attached to this material.'], 404);
        }

        // Strip the storage base URL to get a relative path within the public disk
        $storageBaseUrl = rtrim(Storage::disk('public')->url(''), '/');
        $isStorageFile = str_starts_with($downloadUrl, $storageBaseUrl);

        if ($isStorageFile) {
            $relativePath = ltrim(substr($downloadUrl, strlen($storageBaseUrl)), '/');

            if (! Storage::disk('public')->exists($relativePath)) {
                return response()->json(['status' => 'error', 'message' => 'File not found on server.'], 404);
            }

            $extension   = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
            $safeFilename = Str::slug($material->title) . ($extension ? '.' . $extension : '');

            return Storage::disk('public')->download($relativePath, $safeFilename);
        }

        // External URL (e.g. Google Drive) — proxy the bytes so the browser gets a real download
        $context = stream_context_create(['http' => ['follow_location' => true]]);
        $fileContents = @file_get_contents($downloadUrl, false, $context);

        if ($fileContents === false) {
            return response()->json(['status' => 'error', 'message' => 'Could not retrieve the file from the remote URL.'], 502);
        }

        $extension    = strtolower(pathinfo(parse_url($downloadUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $safeFilename = Str::slug($material->title) . ($extension ? '.' . $extension : '');
        $mimeType     = 'application/octet-stream';

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($fileContents);
        if ($detected !== false) {
            $mimeType = $detected;
        }

        return response()->stream(
            fn () => print($fileContents),
            200,
            [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $safeFilename . '"',
                'Content-Length'      => strlen($fileContents),
            ]
        );
    }

    private function formatMaterial(Material $material, bool $includeDownload): array
    {
        return [
            'id' => $material->id,
            'title' => $material->title,
            'category' => $material->category,
            'access_type' => $material->access_type,
            'price' => (float) $material->price,
            'format' => $material->format,
            'description' => $material->description,
            'cover_url' => $material->cover_url,
            'download_url' => $includeDownload ? $material->download_url : null,
            'is_published' => (bool) $material->is_published,
        ];
    }

    private function resolveAuthenticatedUser(Request $request): ?User
    {
        return $request->user('sanctum') ?? $request->user();
    }

    private function userHasMaterialAccess(?User $user, Material $material): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return Purchase::query()
            ->where('user_id', $user->id)
            ->where('item_type', 'material')
            ->where('item_id', $material->id)
            ->where('status', 'paid')
            ->exists();
    }
}
