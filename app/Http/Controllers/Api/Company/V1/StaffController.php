<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Http\Controllers\Api\Company\V1\Concerns\PaginationMeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreStaffRequest;
use App\Http\Requests\Company\UpdateStaffRequest;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Company\CompanyUserResource;
use App\Models\CompanyUser;
use App\Services\Company\CompanyStaffService;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    use PaginationMeta;

    public function __construct(protected CompanyStaffService $staffService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $staff = CompanyUser::query()
            ->where('company_id', Tenant::companyId())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            CompanyUserResource::collection($staff)->resolve(),
            'OK',
            200,
            $this->meta($staff)
        );
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = $this->staffService->create(
            Tenant::companyId(),
            $request->validated(),
            $request->user(),
        );

        return ApiResponse::success(
            (new CompanyUserResource($staff))->resolve(),
            'تمت إضافة الموظف.',
            201
        );
    }

    public function update(UpdateStaffRequest $request, int $staff): JsonResponse
    {
        $member = $this->findStaff($staff);

        $member = $this->staffService->update($member, $request->validated(), $request->user());

        return ApiResponse::success(
            (new CompanyUserResource($member))->resolve(),
            'تم تحديث بيانات الموظف.'
        );
    }

    public function toggleStatus(Request $request, int $staff): JsonResponse
    {
        $member = $this->staffService->toggleStatus($this->findStaff($staff), $request->user());

        return ApiResponse::success(
            (new CompanyUserResource($member))->resolve(),
            'تم تحديث حالة الموظف.'
        );
    }

    protected function findStaff(int $id): CompanyUser
    {
        return CompanyUser::query()
            ->where('company_id', Tenant::companyId())
            ->whereKey($id)
            ->firstOrFail();
    }
}
