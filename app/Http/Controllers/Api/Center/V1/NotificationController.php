<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\DatabaseNotification;
use App\Services\Notifications\DestinationResolver;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notifications): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->integer('per_page', 20)));
        $query = $request->user()->notifications()->active();

        if ($request->filled('type')) {
            $query->ofType($request->string('type')->toString());
        }
        if ($request->string('read') === 'unread') {
            $query->unread();
        } elseif ($request->string('read') === 'read') {
            $query->read();
        }

        $paginator = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => collect($paginator->items())->map(
                fn (DatabaseNotification $n) => $n->toListArray('center')
            ),
            'unread_count' => $notifications->unreadCount($request->user()),
        ], 'OK', 200, [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function unreadCount(Request $request, NotificationService $notifications): JsonResponse
    {
        return ApiResponse::success([
            'unread_count' => $notifications->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, string $id, NotificationService $notifications): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        return ApiResponse::success([
            'unread_count' => $notifications->unreadCount($request->user()),
        ], 'تم تعليم الإشعار كمقروء.');
    }

    public function markAllRead(Request $request, NotificationService $notifications): JsonResponse
    {
        $updated = $notifications->markAllRead($request->user());

        return ApiResponse::success([
            'updated' => $updated,
            'unread_count' => 0,
        ], 'تم تعليم الكل كمقروء.');
    }

    public function destroy(Request $request, string $id, NotificationService $notifications): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notifications->deleteOwned($notification);

        return ApiResponse::success([
            'unread_count' => $notifications->unreadCount($request->user()),
        ], 'تم حذف الإشعار.');
    }

    public function open(
        Request $request,
        string $id,
        NotificationService $notifications,
        DestinationResolver $destinations,
    ): JsonResponse {
        $notification = $request->user()->notifications()->active()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        $result = $destinations->inspect($notification, 'center', $request->user());

        return ApiResponse::success([
            'ok' => $result['available'],
            'url' => $result['url'],
            'reason' => $result['reason'],
            'deep_link' => sprintf('knooz://center/notifications/%s', $notification->id),
            'payload' => [
                'notification_id' => $notification->id,
                'notification_type' => $notification->businessType()?->value,
                'reference_type' => $notification->reference_type,
                'reference_id' => $notification->reference_id,
            ],
            'unread_count' => $notifications->unreadCount($request->user()),
        ], $result['available'] ? 'OK' : 'الوجهة غير متاحة.', $result['available'] ? 200 : 409);
    }
}
