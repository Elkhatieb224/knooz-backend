<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Enums\OperationImageType;
use App\Enums\OperationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Center\LookupOperationRequest;
use App\Http\Requests\Api\Center\StoreOperationImageRequest;
use App\Http\Requests\Api\Center\StoreOperationRequest;
use App\Http\Requests\Api\Center\UpdateOperationRequest;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Center\OperationResource;
use App\Models\Operation;
use App\Models\OperationImage;
use App\Services\Center\OperationCreateService;
use App\Services\Center\OperationLookupService;
use App\Services\Notifications\NotificationHooks;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OperationController extends Controller
{
    public function __construct(
        protected OperationLookupService $lookup,
        protected OperationCreateService $create,
        protected NotificationHooks $notificationHooks,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $centerId = Tenant::centerId();
        $perPage = min(50, max(1, (int) $request->integer('per_page', 15)));

        $operations = Operation::query()
            ->with(['user', 'vehicle', 'service', 'createdByCenterUser', 'images'])
            ->forCenter($centerId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', $term));
                });
            })
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            OperationResource::collection($operations)->resolve(),
            'OK',
            200,
            [
                'current_page' => $operations->currentPage(),
                'last_page' => $operations->lastPage(),
                'per_page' => $operations->perPage(),
                'total' => $operations->total(),
            ]
        );
    }

    public function show(int $id): JsonResponse
    {
        $operation = $this->findOwned($id);

        return ApiResponse::success(new OperationResource($operation));
    }

    public function lookup(LookupOperationRequest $request): JsonResponse
    {
        /** @var \App\Models\CenterUser $user */
        $user = $request->user();
        $result = $this->lookup->lookup(
            $user->center,
            $request->validated('method'),
            $request->validated('value'),
        );

        return ApiResponse::success($result);
    }

    public function store(StoreOperationRequest $request): JsonResponse
    {
        /** @var \App\Models\CenterUser $user */
        $user = $request->user();
        $operations = $this->create->create($user->center, $user, $request->validated());

        return ApiResponse::success(
            OperationResource::collection(collect($operations)),
            'تم إنشاء العملية.',
            201
        );
    }

    public function update(UpdateOperationRequest $request, int $id): JsonResponse
    {
        $operation = $this->findOwned($id);
        $data = $request->validated();

        if (isset($data['status'])) {
            $status = $data['status'] instanceof OperationStatus
                ? $data['status']
                : OperationStatus::from($data['status']);

            $data['status'] = $status;

            if ($status === OperationStatus::Completed) {
                $data['completed_at'] = $operation->completed_at ?? now();
            }

            if ($status === OperationStatus::InProgress) {
                $data['started_at'] = $operation->started_at ?? now();
            }
        }

        $operation->update($data);
        $this->notificationHooks->operationUpdated($operation->fresh(['vehicle']));

        return ApiResponse::success(
            new OperationResource($operation->fresh(['user', 'vehicle', 'service', 'createdByCenterUser', 'images'])),
            'تم تحديث العملية.'
        );
    }

    public function storeImage(StoreOperationImageRequest $request, int $id): JsonResponse
    {
        $operation = $this->findOwned($id);
        $type = $request->validated('type');
        $typeValue = $type instanceof OperationImageType ? $type->value : $type;

        $path = $request->file('image')->store(
            'operations/'.$operation->id,
            'public'
        );

        $image = OperationImage::query()->create([
            'operation_id' => $operation->id,
            'path' => $path,
            'type' => $typeValue,
        ]);

        return ApiResponse::success([
            'id' => $image->id,
            'type' => $image->type?->value,
            'path' => $image->path,
            'url' => Storage::disk('public')->url($image->path),
        ], 'تم رفع الصورة.', 201);
    }

    protected function findOwned(int $id): Operation
    {
        return Operation::query()
            ->with(['user', 'vehicle', 'service', 'createdByCenterUser', 'images'])
            ->forCenter(Tenant::centerId())
            ->whereKey($id)
            ->firstOrFail();
    }
}
