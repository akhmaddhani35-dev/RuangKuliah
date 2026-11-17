<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasSharedRecords
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query->whereHas('user', fn (Builder $owner) => $owner->where('role', 'admin'));
        }

        $userIdColumn = $query->getModel()->qualifyColumn('user_id');

        return $query->where(function (Builder $records) use ($user, $userIdColumn) {
            $records->where($userIdColumn, $user->id)
                ->orWhereHas('user', fn (Builder $owner) => $owner->where('role', 'admin'));
        });
    }

    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query->whereHas('user', fn (Builder $owner) => $owner->where('role', 'admin'));
        }

        return $query->where($query->getModel()->qualifyColumn('user_id'), $user->id);
    }
}
