<?php

namespace App\Actions;

use App\Exceptions\InternshipApplicationException;
use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateInternshipApplication
{
    /**
     * Izveido jaunu prakses pieteikumu ar transakciju.
     *
     * @param array{
     *   user_id: int,
     *   group_id?: int,
     *   internship_id: int,
     *   motivation_letter?: string|null
     * } $data
     *
     * @throws InternshipApplicationException
     */
    public function execute(array $data): Application
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['user_id'];
            $internshipId = $data['internship_id'];
            $motivationLetter = $data['motivation_letter'] ?? null;
            $sentGroupId = $data['group_id'] ?? null;

            // a) Pārbauda, vai lietotājs ir datubāzē
            $user = $this->validateUserExists($userId);

            // a2) Pārbauda, vai nosūtītā group_id sakrīt ar lietotāja group_id
            if ($sentGroupId !== null && $sentGroupId !== $user->groups_id) {
                throw InternshipApplicationException::groupMismatch($sentGroupId, $user->groups_id);
            }

            // b) Pārbauda, vai prakse ir derīga
            $internship = $this->validateInternshipIsValid($internshipId);

            // c) Pārbauda, vai lietotājam atļauts pieteikties šajā praksē
            $this->validateUserAllowed($user, $internship);

            // Pārbauda, vai pieteikums jau neeksistē
            $this->validateApplicationDoesNotExist($userId, $internshipId);

            // Izveido prakses pieprasījumu
            return Application::create([
                'users_id' => $userId,
                'group_id' => $user->groups_id,
                'internships_id' => $internshipId,
                'motivation_letter' => $motivationLetter,
            ]);
        });
    }

    /**
     * Validē, ka lietotājs eksistē datubāzē.
     *
     * @throws InternshipApplicationException
     */
    private function validateUserExists(int $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw InternshipApplicationException::userNotFound($userId);
        }

        return $user;
    }

    /**
     * Validē, ka prakse eksistē un ir derīga (ir aktīvs laika periods).
     *
     * @throws InternshipApplicationException
     */
    private function validateInternshipIsValid(int $internshipId): Internship
    {
        $internship = Internship::find($internshipId);

        if (!$internship) {
            throw InternshipApplicationException::internshipNotFound($internshipId);
        }

        // Pārbauda, vai ir vismaz viena grupa, kurai prakse ir aktīva
        $validGroupInternship = $internship->groups()
            ->wherePivot('start_at', '<=', now())
            ->wherePivot('end_at', '>=', now())
            ->first();

        if (!$validGroupInternship) {
            throw InternshipApplicationException::internshipNotValid($internshipId);
        }

        return $internship;
    }

    /**
     * Validē, ka lietotājam ir atļauts pieteikties šajā praksē.
     * Lietotājs drīkst pieteikties tikai praksēm, kas ir saistītas ar viņa grupu.
     *
     * @throws InternshipApplicationException
     */
    private function validateUserAllowed(User $user, Internship $internship): void
    {
        // Pārbauda, vai lietotāja grupa ir saistīta ar šo praksi
        $groupInternship = $user->group()
            ->whereHas('internships', function ($query) use ($internship) {
                $query->where('internships.id', $internship->id);
            })
            ->first();

        if (!$groupInternship) {
            throw InternshipApplicationException::userNotAllowed($user->id, $internship->id);
        }
    }

    /**
     * Validē, ka pieteikums vēl neeksistē.
     *
     * @throws InternshipApplicationException
     */
    private function validateApplicationDoesNotExist(int $userId, int $internshipId): void
    {
        $exists = Application::where('users_id', $userId)
            ->where('internships_id', $internshipId)
            ->exists();

        if ($exists) {
            throw InternshipApplicationException::applicationAlreadyExists($userId, $internshipId);
        }
    }
}
