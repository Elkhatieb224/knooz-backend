<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Center\BookingResource;
use App\Http\Resources\Center\OperationResource;
use App\Models\Booking;
use App\Models\Operation;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $centerId = Tenant::centerId();
        $perPage = min(50, max(1, (int) $request->integer('per_page', 15)));

        $ops = Operation::query()
            ->select('user_id', DB::raw('MAX(created_at) as last_visit_at'), DB::raw('COUNT(*) as operations_count'))
            ->forCenter($centerId)
            ->groupBy('user_id');

        $bookings = Booking::query()
            ->select('user_id', DB::raw('MAX(created_at) as last_booking_at'), DB::raw('COUNT(*) as bookings_count'))
            ->forCenter($centerId)
            ->groupBy('user_id');

        $query = User::query()
            ->from('users')
            ->leftJoinSub($ops, 'ops', 'ops.user_id', '=', 'users.id')
            ->leftJoinSub($bookings, 'bks', 'bks.user_id', '=', 'users.id')
            ->where(function ($q) {
                $q->whereNotNull('ops.user_id')->orWhereNotNull('bks.user_id');
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('users.name', 'like', $term)
                        ->orWhere('users.phone', 'like', $term)
                        ->orWhere('users.email', 'like', $term);
                });
            })
            ->select([
                'users.id',
                'users.name',
                'users.phone',
                'users.email',
                'ops.operations_count',
                'ops.last_visit_at',
                'bks.bookings_count',
                'bks.last_booking_at',
            ])
            ->orderByRaw('COALESCE(ops.last_visit_at, bks.last_booking_at) DESC');

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'phone' => $row->phone,
            'email' => $row->email,
            'operations_count' => (int) ($row->operations_count ?? 0),
            'bookings_count' => (int) ($row->bookings_count ?? 0),
            'last_visit_at' => $row->last_visit_at,
        ]);

        return ApiResponse::success($items, 'OK', 200, [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function show(int $userId): JsonResponse
    {
        $centerId = Tenant::centerId();

        $exists = Operation::query()->forCenter($centerId)->where('user_id', $userId)->exists()
            || Booking::query()->forCenter($centerId)->where('user_id', $userId)->exists();

        if (! $exists) {
            abort(404);
        }

        $user = User::query()->findOrFail($userId);

        $operations = Operation::query()
            ->with(['vehicle', 'service'])
            ->forCenter($centerId)
            ->where('user_id', $userId)
            ->latest()
            ->limit(50)
            ->get();

        $bookings = Booking::query()
            ->with(['vehicle', 'service'])
            ->forCenter($centerId)
            ->where('user_id', $userId)
            ->latest('scheduled_at')
            ->limit(50)
            ->get();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'operations' => OperationResource::collection($operations),
            'bookings' => BookingResource::collection($bookings),
        ]);
    }
}
