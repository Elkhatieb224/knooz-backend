<?php

namespace App\Http\Controllers\Center;

use App\Enums\OperationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Center\UpdateOperationStatusRequest;
use App\Models\Operation;
use App\Services\Notifications\NotificationHooks;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationController extends Controller
{
    public function __construct(protected NotificationHooks $notificationHooks)
    {
    }

    public function index(Request $request): View
    {
        $centerId = Tenant::centerId();

        $operations = Operation::query()
            ->with(['user', 'vehicle', 'service', 'employee'])
            ->forCenter($centerId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', $term));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('center.operations.index', [
            'operations' => $operations,
            'operationStatuses' => OperationStatus::labels(),
        ]);
    }

    public function update(UpdateOperationStatusRequest $request, Operation $operation): RedirectResponse
    {
        abort_unless($operation->center_id === Tenant::centerId(), 404);

        $data = $request->validated();

        if (($data['status'] ?? null) === OperationStatus::Completed->value || ($data['status'] ?? null) === OperationStatus::Completed) {
            $data['completed_at'] = $operation->completed_at ?? now();
        }

        if (($data['status'] ?? null) === OperationStatus::InProgress->value || ($data['status'] ?? null) === OperationStatus::InProgress) {
            $data['started_at'] = $operation->started_at ?? now();
        }

        $operation->update($data);
        $this->notificationHooks->operationUpdated($operation->fresh(['vehicle']));

        return back()->with('success', 'تم تحديث حالة العملية.');
    }
}
