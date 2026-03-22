<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamAccessController extends Controller
{
    public function show(Request $request, Exam $exam): JsonResponse
    {
        $user = $request->user();

        $hasAccess = $exam->access_type === 'free'
            || $user->role === User::ROLE_ADMIN
            || Purchase::query()
                ->where('user_id', $user->id)
                ->where('item_type', 'exam')
                ->where('item_id', $exam->id)
                ->where('status', 'paid')
                ->exists();

        $reason = $exam->access_type === 'free'
            ? 'free'
            : ($hasAccess ? 'purchased' : 'required_payment');

        return response()->json([
            'status' => 'success',
            'data' => [
                'exam_id' => $exam->id,
                'has_access' => $hasAccess,
                'reason' => $reason,
            ],
        ]);
    }
}
