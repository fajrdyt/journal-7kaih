<?php

namespace App\Policies;

use App\Models\DailyCheckin;
use App\Models\StudentParentRelation;
use App\Models\User;
use Illuminate\Support\Carbon;

class CheckinPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active
            && $this->hasAnyRole($user, ['siswa', 'orang_tua', 'guru', 'admin']);
    }

    public function view(User $user, DailyCheckin $checkin): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($this->hasRole($user, 'admin')) {
            return true;
        }

        if ($this->hasRole($user, 'siswa')) {
            return (int) $checkin->student_id === (int) $user->id;
        }

        if ($this->hasRole($user, 'orang_tua')) {
            return $this->parentCanAccessStudent($user->id, $checkin->student_id);
        }

        if ($this->hasRole($user, 'guru')) {
            return $this->teacherCanAccessStudent($user->id, $checkin->student_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active
            && $this->hasRole($user, 'siswa');
    }

    public function update(User $user, DailyCheckin $checkin): bool
    {
        if (!$user->is_active || !$this->hasRole($user, 'siswa')) {
            return false;
        }

        if ((int) $checkin->student_id !== (int) $user->id) {
            return false;
        }

        return Carbon::parse($checkin->checkin_date)->isToday();
    }

    public function delete(User $user, DailyCheckin $checkin): bool
    {
        return false;
    }

    private function hasRole(User $user, string $role): bool
    {
        return $user->role()
            ->where('name', $role)
            ->exists();
    }

    private function hasAnyRole(User $user, array $roles): bool
    {
        return $user->role()
            ->whereIn('name', $roles)
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