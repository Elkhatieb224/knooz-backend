<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Center\StoreComplaintRequest;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Center\ComplaintResource;
use App\Models\Complaint;
use App\Models\Operation;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $centerId = Tenant::centerId();
        $perPage = min(50, max(1, (int) $request->integer('per_page', 15)));

        $complaints = Complaint::query()
            ->with(['user', 'operation'])
            ->where('center_id', $centerId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            ComplaintResource::collection($complaints)->resolve(),
            'OK',
            200,
            [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ]
        );
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        /** @var \App\Models\CenterUser $actor */
        $actor = $request->user();
        $centerId = Tenant::centerId();
        $data = $request->validated();

        $operationId = $data['operation_id'] ?? null;
        $userId = $data['user_id'] ?? null;

        if ($operationId) {
            $operation = Operation::query()
                ->forCenter($centerId)
                ->whereKey($operationId)
                ->firstOrFail();
            $userId = $userId ?: $operation->user_id;
        }

        if (! $userId) {
            // Fallback: attribute complaint to a synthetic center-scoped user reference via actor legacy if needed.
            // Prefer requiring a customer user_id when no operation is linked.
            return ApiResponse::error('يجب تحديد العميل أو العملية.', 422, [
                'user_id' => ['مطلوب عند عدم ربط شكوى بعملية.'],
            ]);
        }

        $complaint = Complaint::query()->create([
            'user_id' => $userId,
            'center_id' => $centerId,
            'operation_id' => $operationId,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => ComplaintStatus::Open,
            'source' => 'center_app',
        ]);

        return ApiResponse::success(
            new ComplaintResource($complaint->load('user')),
            'تم تسجيل الشكوى.',
            201
        );
    }
}
