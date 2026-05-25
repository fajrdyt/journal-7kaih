<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use Illuminate\Http\JsonResponse;

class HabitController extends Controller
{
    public function index(): JsonResponse
    {
        $habits = Habit::active()
            ->get()
            ->map(fn ($habit) => [
                'id'         => $habit->id,
                'code'       => $habit->code,
                'name'       => $habit->name,
                'sort_order' => $habit->sort_order,
                'is_active'  => $habit->is_active,
            ])
            ->values();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar habit berhasil diambil.',
            'data'    => $habits,
        ]);
    }
}