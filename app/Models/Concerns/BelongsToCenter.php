<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCenter
{
    public function scopeForCenter(Builder $query, int|string|null $centerId): Builder
    {
        if ($centerId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($this->qualifyColumn('center_id'), $centerId);
    }
}
