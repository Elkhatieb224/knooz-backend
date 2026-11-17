<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\DatabaseNotification;

/**
 * Maps notification types to Flutter go_router paths for the customer app.
 */
class MobileDestinationResolver
{
    /**
     * @return array{route: string, label: string}
     */
    public function resolve(DatabaseNotification $notification): array
    {
        $type = $notification->businessType();

        return match ($type) {
            NotificationType::BookingNew,
            NotificationType::BookingStatus,
            NotificationType::OperationStatus => [
                'route' => '/app/bookings',
                'label' => 'حجوزاتي',
            ],
            NotificationType::PaymentPending,
            NotificationType::PaymentApproved,
            NotificationType::PaymentRejected => [
                'route' => '/wallet/recharge',
                'label' => 'المحفظة',
            ],
            NotificationType::EmergencyNew,
            NotificationType::EmergencyStatus => $this->emergencyRoute($notification),
            NotificationType::ComplaintNew,
            NotificationType::ComplaintStatus => [
                'route' => '/app/more',
                'label' => 'المزيد',
            ],
            NotificationType::VehicleAssigned => [
                'route' => '/app/vehicles',
                'label' => 'مركباتي',
            ],
            default => [
                'route' => '/app/notifications',
                'label' => 'الإشعارات',
            ],
        };
    }

    /**
     * @return array{route: string, label: string}
     */
    protected function emergencyRoute(DatabaseNotification $notification): array
    {
        $notes = '';
        $ref = $notification->reference;
        if ($ref && isset($ref->notes)) {
            $notes = (string) $ref->notes;
        }

        if (str_starts_with($notes, 'home:')) {
            return [
                'route' => '/services/home',
                'label' => 'خدمة عند المنزل',
            ];
        }

        return [
            'route' => '/services/emergency',
            'label' => 'خدمات الطوارئ',
        ];
    }
}
