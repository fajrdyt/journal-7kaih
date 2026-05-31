<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\StudentParentRelation;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentRelation
{
    public function handle(Request $request, Closure $next): Response
    {
        $parentId  = $request->user()->id;
        $studentId = $request->route('studentId');

        if (!$studentId) {
            return $next($request);
        }

        $hasRelation = StudentParentRelation::where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();

        if (!$hasRelation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki relasi aktif dengan siswa ini.',
            ], 403);
        }

        return $next($request);
    }
}