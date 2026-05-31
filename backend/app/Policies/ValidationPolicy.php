<?php

namespace App\Policies;

use App\Models\DailyCheckin;
use App\Models\DailyCheckinItem;
use App\Models\StudentParentRelation;
use App\Models\User;

class ValidationPolicy
{
    public function viewParentCheckin(User $user, DailyCheckin $checkin): bool
    {
        return $this->validateHome($user, $checkin);
    }

    public function viewTeacherCheckin(User $user, DailyCheckin $checkin): bool
    {
        return $this->validateSchool($user, $checkin);
    }

    public function validateHome(User $user, DailyCheckin $checkin): bool
    {
        if (!$user->is_active || !$this->hasRole($user, 'orang_tua')) {
            return false;
        }

        return $this->parentCanAccessStudent($user->id, $checkin->student_id);
    }

    public function validateHomeItem(User $user, DailyCheckinItem $item): bool
    {
        if (!$item->is_done) {
            return false;
        }

        $checkin = $item->dailyCheckin
            ?: DailyCheckin::query()->find($item->daily_checkin_id);

        if (!$checkin) {
            return false;
        }

        return $this->validateHome($user, $checkin);
    }

    public function validateSchool(User $user, DailyCheckin $checkin): bool
    {
        if (!$user->is_active || !$this->hasRole($user, 'guru')) {
            return false;
        }

        return $this->teacherCanAccessStudent($user->id, $checkin->student_id);
    }

    public function validateSchoolItem(User $user, DailyCheckinItem $item): bool
    {
        if (!$item->is_done) {
            return false;
        }

        $checkin = $item->dailyCheckin
            ?: DailyCheckin::query()->find($item->daily_checkin_id);

        if (!$checkin) {
            return false;
        }

        return $this->validateSchool($user, $checkin);
    }

    private function hasRole(User $user, string $role): bool
    {
        return $user->role()
            ->where('name', $role)
            ->exists();
    }

    private function parentCanAccessStudent(int $parentId, int $studentId): bool
    {
        return StudentParentRelation::query()
            ->where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();
    }

    private function teacherCanAccessStudent(int $teacherId, int $studentId): bool
    {
        return User::query()
            ->where('id', $studentId)
            ->whereNotNull('class_id')
            ->whereHas('classRoom', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->exists();
    }
}