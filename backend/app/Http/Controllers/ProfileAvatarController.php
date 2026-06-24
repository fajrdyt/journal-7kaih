<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProfileAvatarController extends Controller
{
    public function update(
        UpdateAvatarRequest $request
    ): JsonResponse {
        $disk = Storage::disk('public');

        $newAvatarPath = $request
            ->file('avatar')
            ->store('avatars', 'public');

        if (
            ! is_string($newAvatarPath) ||
            $newAvatarPath === ''
        ) {
            throw new RuntimeException(
                'Foto profil gagal disimpan.'
            );
        }

        $oldAvatarPath = null;

        try {
            $user = DB::transaction(
                function () use (
                    $request,
                    $newAvatarPath,
                    &$oldAvatarPath
                ): User {
                    $user = User::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $request->user()->getKey()
                        );

                    $oldAvatarPath = $user->avatar_path;

                    $user->avatar_path = $newAvatarPath;
                    $user->save();
                    $user->refresh();

                    return $user;
                },
                3
            );
        } catch (Throwable $exception) {
            $disk->delete($newAvatarPath);

            throw $exception;
        }

        $this->deleteManagedAvatar($oldAvatarPath);

        return response()->json([
            'status' => 'success',
            'message' => 'Foto profil berhasil diperbarui.',
            'data' => [
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    public function destroy(
        Request $request
    ): JsonResponse {
        $oldAvatarPath = null;

        $user = DB::transaction(
            function () use (
                $request,
                &$oldAvatarPath
            ): User {
                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $request->user()->getKey()
                    );

                $oldAvatarPath = $user->avatar_path;

                if ($oldAvatarPath !== null) {
                    $user->avatar_path = null;
                    $user->save();
                    $user->refresh();
                }

                return $user;
            },
            3
        );

        $this->deleteManagedAvatar($oldAvatarPath);

        return response()->json([
            'status' => 'success',
            'message' => $oldAvatarPath
                ? 'Foto profil berhasil dihapus.'
                : 'Foto profil sudah tidak tersedia.',
            'data' => [
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    private function deleteManagedAvatar(
        ?string $avatarPath
    ): void {
        if (! $this->isManagedAvatarPath($avatarPath)) {
            return;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($avatarPath)) {
                return;
            }

            if (! $disk->delete($avatarPath)) {
                Log::warning(
                    'File avatar gagal dihapus.',
                    [
                        'avatar_path' => $avatarPath,
                    ]
                );
            }
        } catch (Throwable $exception) {
            Log::warning(
                'Terjadi kesalahan saat menghapus file avatar.',
                [
                    'avatar_path' => $avatarPath,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    private function isManagedAvatarPath(
        ?string $avatarPath
    ): bool {
        return is_string($avatarPath)
            && str_starts_with(
                $avatarPath,
                'avatars/'
            )
            && ! str_contains($avatarPath, '..');
    }
}