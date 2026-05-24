<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use Illuminate\Http\JsonResponse;

class HabitController extends Controller
{
    public function index(): JsonResponse
    {
        $habits = Habit::active()->get()->map(fn($habit) => [
            'id'                      => $habit->id,
            'code'                    => $habit->code,
            'name'                    => $habit->name,
            'default_activity_context'=> $habit->default_activity_context,
            'sort_order'              => $habit->sort_order,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kebiasaan berhasil diambil.',
            'data'    => $habits,
        ]);
    }
}