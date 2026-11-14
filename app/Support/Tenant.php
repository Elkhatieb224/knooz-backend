<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class Tenant
{
    public static function companyId(): ?int
    {
        $user = Auth::guard('company')->user();

        return $user?->company_id;
    }

    public static function centerId(): ?int
    {
        $user = Auth::guard('center')->user();

        return $user?->center_id;
    }
}
