<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdminMaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $materials = Material::query()
            ->latest()
            ->paginate($request->integer('per_page', 50))
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $materials->getCollection()->map(fn (Material $material) => $this->formatMaterial($material))->all(),
                'meta' => [
                    'current_page' => $materials->currentPage(),
                    'per_page' => $materials->perPage(),
                    'total' => $materials->total(),
                    'last_page' => $materials->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'access_type' => ['required', 'in:free,paid'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'format' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'download_url' => ['nullable', 'string', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $material = Material::create([
            ...$data,
            'price' => $data['access_type'] === 'free' ? 0 : ($data['price'] ?? 0),
            'is_published' => $data['is_published'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Material created',
            'data' => [
                'material' => $this->formatMaterial($material),
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(Material $material): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'material' => $this->formatMaterial($material),
            ],
        ]);
    }

    public function update(Request $request, Material $material): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'max:255'],
            'access_type' => ['sometimes', 'required', 'in:free,paid'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'format' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'download_url' => ['nullable', 'string', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if (($data['access_type'] ?? $material->access_type) === 'free') {
            $data['price'] = 0;
        }

        $material->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Material updated',
            'data' => [
                'material' => $this->formatMaterial($material->fresh()),
            ],
        ]);
    }

    public function destroy(Material $material): JsonResponse
    {
        $material->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Material deleted',
        ]);
    }

    public function extractZip(Request $request): JsonResponse
    {
        $request->validate([
            'zip' => ['required', 'file'],
        ]);

        $tmpPath = $request->file('zip')->getRealPath();
        $zip = new \ZipArchive();
        $openResult = $zip->open($tmpPath);

        if ($openResult !== true) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or corrupt ZIP file. Please upload a valid .zip archive.'], 422);
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg'];

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            // Skip macOS metadata, hidden files, directories, path traversal
            if (str_contains($name, '__MACOSX') || str_starts_with(basename($name), '.')) continue;
            if (str_contains($name, '..') || str_starts_with($name, '/')) continue;
            if (str_ends_with($name, '/')) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) continue;

            $bytes = $zip->getFromIndex($i);
            $safeFilename = Str::slug(pathinfo($name, PATHINFO_FILENAME)) . '_' . uniqid() . '.' . $ext;
            Storage::disk('public')->put("materials/{$safeFilename}", $bytes);

            $title = ucwords(str_replace(['-', '_'], ' ', pathinfo($name, PATHINFO_FILENAME)));
            $files[] = [
                'title' => $title,
                'url'   => Storage::disk('public')->url("materials/{$safeFilename}"),
                'format' => strtoupper($ext),
            ];
        }
        $zip->close();

        if (empty($files)) {
            return response()->json(['status' => 'error', 'message' => 'No supported files found in the ZIP. Accepted formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, PNG, JPG.'], 422);
        }

        return response()->json(['status' => 'success', 'data' => ['files' => $files]]);
    }

    private function formatMaterial(Material $material): array
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
            'download_url' => $material->download_url,
            'is_published' => (bool) $material->is_published,
        ];
    }
}
