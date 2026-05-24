<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\StudentParentRelation;
use App\Services\ValidationService;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function __construct(protected ValidationService $service) {}

    // ── PARENT ────────────────────────────────────────────────

    /**
     * GET /parent/children
     */
    public function children(Request $request)
    {
        $children = StudentParentRelation::with('student.classRoom')
            ->where('parent_id', $request->user()->id)
            ->active()
            ->get()
            ->map(fn($rel) => [
                'student_id'          => $rel->student->id,
                'student_name'        => $rel->student->full_name,
                'class'               => $rel->student->classRoom ? [
                    'id'          => $rel->student->classRoom->id,
                    'name'        => $rel->student->classRoom->name,
                    'grade_level' => $rel->student->classRoom->grade_level,
                ] : null,
                'relation_type'       => $rel->relation_type,
                'latest_checkin_date' => $rel->student->checkins()
                    ->latest('checkin_date')
                    ->value('checkin_date'),
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar anak berhasil diambil.',
            'data'    => $children,
        ]);
    }

    /**
     * GET /parent/children/{studentId}/checkins
     */
    public function childCheckins(Request $request, int $studentId)
    {
        $this->service->assertParentHasChild($request->user()->id, $studentId);

        $query = DailyCheckin::with('items.validation')
            ->where('student_id', $studentId)
            ->orderByDesc('checkin_date');

        if ($request->start_date) {
            $query->whereDate('checkin_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('checkin_date', '<=', $request->end_date);
        }

        $checkins = $query->paginate($request->per_page ?? 10);

        $items = $checkins->map(fn($c) => [
            'id'                          => $c->id,
            'checkin_date'                => $c->checkin_date->format('Y-m-d'),
            'notes'                       => $c->notes,
            'total_habits_done'           => $c->items->where('is_done', true)->count(),
            'total_items_validated'       => $c->items->filter(fn($i) => $i->validation)->count(),
            'home_pending_validation_count' => $c->items->filter(
                fn($i) => $i->is_done && $i->activity_context === 'rumah' && !$i->validation
            )->count(),
            'school_validated_count'      => $c->items->filter(
                fn($i) => $i->validation && $i->validation->validation_source === 'sekolah'
            )->count(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar check-in anak berhasil diambil.',
            'data'    => [
                'items'      => $items,
                'pagination' => [
                    'page'        => $checkins->currentPage(),
                    'per_page'    => $checkins->perPage(),
                    'total'       => $checkins->total(),
                    'total_pages' => $checkins->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /parent/checkins/{id}
     */
    public function parentCheckinDetail(Request $request, int $id)
    {
        $checkin = DailyCheckin::with('items.habit', 'items.validation.validator', 'student.classRoom')
            ->findOrFail($id);

        $this->service->assertParentHasChild($request->user()->id, $checkin->student_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail check-in berhasil diambil.',
            'data'    => $this->formatCheckinDetail($checkin),
        ]);
    }

    /**
     * POST /parent/checkins/{id}/validate-home
     */
    public function validateHome(Request $request, int $id)
    {
        $checkin = DailyCheckin::findOrFail($id);
        $this->service->assertParentHasChild($request->user()->id, $checkin->student_id);

        $result = $this->service->validateBatch(
            $id,
            $request->user()->id,
            'orang_tua',
            'rumah',
            $request->input('item_ids')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Validasi rumah berhasil diproses.',
            'data'    => $result,
        ]);
    }

    /**
     * POST /parent/checkin-items/{id}/validate
     */
    public function validateHomeItem(Request $request, int $id)
    {
        $item    = DailyCheckinItem::with('dailyCheckin')->findOrFail($id);
        $this->service->assertParentHasChild($request->user()->id, $item->dailyCheckin->student_id);

        $result = $this->service->validateItem($id, $request->user()->id, 'orang_tua', 'rumah');

        return response()->json([
            'status'  => 'success',
            'message' => 'Item berhasil divalidasi.',
            'data'    => $result,
        ]);
    }

    // ── TEACHER ───────────────────────────────────────────────

    /**
     * GET /teacher/checkins/{id}
     */
    public function teacherCheckinDetail(Request $request, int $id)
    {
        $checkin = DailyCheckin::with('items.habit', 'items.validation.validator', 'student.classRoom')
            ->findOrFail($id);

        $this->service->assertTeacherHasStudent($request->user()->id, $checkin->student_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail check-in berhasil diambil.',
            'data'    => $this->formatCheckinDetail($checkin),
        ]);
    }

    /**
     * POST /teacher/checkins/{id}/validate-school
     */
    public function validateSchool(Request $request, int $id)
    {
        $checkin = DailyCheckin::findOrFail($id);
        $this->service->assertTeacherHasStudent($request->user()->id, $checkin->student_id);

        $result = $this->service->validateBatch(
            $id,
            $request->user()->id,
            'guru',
            'sekolah',
            $request->input('item_ids')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Validasi sekolah berhasil diproses.',
            'data'    => $result,
        ]);
    }

    /**
     * POST /teacher/checkin-items/{id}/validate
     */
    public function validateSchoolItem(Request $request, int $id)
    {
        $item = DailyCheckinItem::with('dailyCheckin')->findOrFail($id);
        $this->service->assertTeacherHasStudent($request->user()->id, $item->dailyCheckin->student_id);

        $result = $this->service->validateItem($id, $request->user()->id, 'guru', 'sekolah');

        return response()->json([
            'status'  => 'success',
            'message' => 'Item berhasil divalidasi.',
            'data'    => $result,
        ]);
    }

    // ── Helper ────────────────────────────────────────────────

    private function formatCheckinDetail(DailyCheckin $checkin): array
    {
        return [
            'id'                    => $checkin->id,
            'student_id'            => $checkin->student_id,
            'checkin_date'          => $checkin->checkin_date->format('Y-m-d'),
            'notes'                 => $checkin->notes,
            'submitted_at'          => $checkin->submitted_at?->toIso8601String(),
            'updated_at'            => $checkin->updated_at?->toIso8601String(),
            'total_habits_done'     => $checkin->items->where('is_done', true)->count(),
            'total_items_validated' => $checkin->items->filter(fn($i) => $i->validation)->count(),
            'student' => [
                'id'        => $checkin->student->id,
                'full_name' => $checkin->student->full_name,
                'class'     => $checkin->student->classRoom ? [
                    'id'          => $checkin->student->classRoom->id,
                    'name'        => $checkin->student->classRoom->name,
                    'grade_level' => $checkin->student->classRoom->grade_level,
                ] : null,
            ],
            'items' => $checkin->items->map(fn($item) => [
                'id'               => $item->id,
                'habit_id'         => $item->habit_id,
                'habit_code'       => $item->habit->code,
                'habit_name'       => $item->habit->name,
                'is_done'          => $item->is_done,
                'activity_context' => $item->activity_context,
                'validation'       => $item->validation ? [
                    'id'                => $item->validation->id,
                    'validator_id'      => $item->validation->validator_id,
                    'validator_name'    => $item->validation->validator->full_name,
                    'validator_role'    => $item->validation->validator_role,
                    'validation_source' => $item->validation->validation_source,
                    'validated_at'      => $item->validation->validated_at?->toIso8601String(),
                ] : null,
            ]),
        ];
    }
}