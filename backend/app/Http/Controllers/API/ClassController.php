<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * GET /api/v1/classes
     * Ambil daftar kelas dengan pagination, search, dan filter is_active.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClassRoom::query();

        // Filter pencarian berdasarkan nama kelas
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter is_active (0 atau 1)
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        $perPage  = $request->integer('per_page', 10);
        $classes  = $query->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kelas berhasil diambil.',
            'data'    => $classes->items(),
            'meta'    => [
                'current_page' => $classes->currentPage(),
                'last_page'    => $classes->lastPage(),
                'per_page'     => $classes->perPage(),
                'total'        => $classes->total(),
            ],
        ]);
    }
}