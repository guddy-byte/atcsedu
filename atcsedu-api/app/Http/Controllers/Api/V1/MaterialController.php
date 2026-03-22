<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $hasAccess = $material->access_type === 'free' || $this->userHasMaterialAccess($request->user(), $material);

        return response()->json([
            'status' => 'success',
            'data' => [
                'material' => $this->formatMaterial($material, $hasAccess),
            ],
        ]);
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
