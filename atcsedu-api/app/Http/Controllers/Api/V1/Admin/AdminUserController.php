<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->latest()
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'registered_at' => $user->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'users' => $users,
            ],
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->role !== User::ROLE_STUDENT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only registered student users can be deleted from this list.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }
}
