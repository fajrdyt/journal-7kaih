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
    public function update(UpdateAvatarRequest $request): JsonResponse
    {
        $disk = Storage::disk('public');
        $newAvatarPath = null;

        try {
            $storedPath = $request
                ->file('avatar')
                ->store('avatars', 'public');

            if (! is_string($storedPath) || trim($storedPath) === '') {
                throw new RuntimeException('Foto profil gagal disimpan.');
            }

            $newAvatarPath = $storedPath;
            $oldAvatarPath = null;

            $user = DB::transaction(
                function () use ($request, $newAvatarPath, &$oldAvatarPath): User {
                    $user = User::query()
                        ->lockForUpdate()
                        ->findOrFail($request->user()->getKey());

                    $oldAvatarPath = $user->getRawOriginal('avatar_path');

                    $user->forceFill([
                        'avatar_path' => $newAvatarPath,
                    ]);

                    if (! $user->save()) {
                        throw new RuntimeException(
                            'Foto profil gagal dicatat ke database.'
                        );
                    }

                    $user->refresh();

                    if ($user->getRawOriginal('avatar_path') !== $newAvatarPath) {
                        throw new RuntimeException(
                            'Path foto profil gagal tersimpan.'
                        );
                    }

                    return $user;
                },
                3
            );

            if ($oldAvatarPath && $oldAvatarPath !== $newAvatarPath) {
                $this->deleteManagedAvatar($oldAvatarPath);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Foto profil berhasil diperbarui.',
                'data' => [
                    'avatar_url' => $user->avatar_url,
                    'user' => $user->loadMissing([
                        'role',
                        'classRoom',
                    ]),
                ],
            ]);
        } catch (Throwable $exception) {
            if (is_string($newAvatarPath) && $newAvatarPath !== '') {
                try {
                    $disk->delete($newAvatarPath);
                } catch (Throwable $deleteException) {
                    Log::warning(
                        'File avatar baru gagal dibersihkan setelah upload gagal.',
                        [
                            'avatar_path' => $newAvatarPath,
                            'error' => $deleteException->getMessage(),
                        ]
                    );
                }
            }

            throw $exception;
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $oldAvatarPath = null;

        $user = DB::transaction(
            function () use ($request, &$oldAvatarPath): User {
                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail($request->user()->getKey());

                $oldAvatarPath = $user->getRawOriginal('avatar_path');

                if ($oldAvatarPath) {
                    $user->forceFill([
                        'avatar_path' => null,
                    ]);

                    if (! $user->save()) {
                        throw new RuntimeException(
                            'Foto profil gagal dihapus dari database.'
                        );
                    }

                    $user->refresh();

                    if ($user->getRawOriginal('avatar_path') !== null) {
                        throw new RuntimeException(
                            'Path foto profil gagal dihapus.'
                        );
                    }
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
                'avatar_url' => null,
                'user' => $user->loadMissing([
                    'role',
                    'classRoom',
                ]),
            ],
        ]);
    }

    private function deleteManagedAvatar(?string $avatarPath): void
    {
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

    private function isManagedAvatarPath(?string $avatarPath): bool
    {
        if (! is_string($avatarPath)) {
            return false;
        }

        $normalizedPath = str_replace(
            '\\',
            '/',
            trim($avatarPath)
        );

        return $normalizedPath !== ''
            && str_starts_with($normalizedPath, 'avatars/')
            && ! str_contains($normalizedPath, '..');
    }
}
