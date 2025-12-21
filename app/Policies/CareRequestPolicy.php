<?php

namespace App\Policies;

use App\Models\CareRequest;
use App\Models\User;

class CareRequestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function view(User $user, CareRequest $careRequest): bool
    {
        if ($user->role === 'ADMIN') return true;

        if ($careRequest->patient_user_id === $user->id) return true;

        if ($user->role === 'NURSE') {
            if ($careRequest->nurse_user_id && $careRequest->nurse_user_id === $user->id) return true;

            // open request visible to nurses
            if ($careRequest->status === 'PENDING' && $careRequest->nurse_user_id === null) return true;
        }

        return false;
    }

    public function accept(User $user, CareRequest $careRequest): bool
    {
        return $user->role === 'NURSE'
            && $careRequest->status === 'PENDING'
            && $careRequest->nurse_user_id === null;
    }

    public function cancel(User $user, CareRequest $careRequest): bool
    {
        return $user->role === 'PATIENT'
            && $careRequest->patient_user_id === $user->id
            && in_array($careRequest->status, ['PENDING', 'ACCEPTED'], true);
    }

    public function complete(User $user, CareRequest $careRequest): bool
    {
        return $user->role === 'NURSE'
            && $careRequest->nurse_user_id === $user->id
            && $careRequest->status === 'ACCEPTED';
    }

    public function ignore(User $user, CareRequest $careRequest): bool
    {
        return $user->role === 'NURSE'
            && $careRequest->status === 'PENDING'
            && $careRequest->nurse_user_id === null;
    }
}
