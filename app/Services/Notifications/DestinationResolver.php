<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\Booking;
use App\Models\Center;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\DatabaseNotification;
use App\Models\EmergencyRequest;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Vehicle;
use App\Support\Permissions;
use App\Support\Tenant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class DestinationResolver
{
    /**
     * Resolve a safe destination URL for the authenticated panel user.
     * Returns null when destination is unavailable or unauthorized.
     */
    public function resolve(DatabaseNotification $notification, string $panel, Authenticatable $user): ?string
    {
        $type = $notification->businessType() ?? NotificationType::System;
        $reference = $this->loadReference($notification);

        if ($notification->reference_type && $notification->reference_id && ! $reference) {
            return null;
        }

        if ($reference && ! $this->canAccessReference($panel, $user, $reference, $type)) {
            return null;
        }

        $route = $this->routeFor($panel, $type, $reference, $notification);

        if (! $route) {
            return $this->fallback($panel);
        }

        if (! Route::has($route['name'])) {
            return $this->fallback($panel);
        }

        try {
            return route($route['name'], $route['params'] ?? []);
        } catch (\Throwable) {
            return $this->fallback($panel);
        }
    }

    /**
     * @return array{available: bool, url: ?string, reason: ?string}
     */
    public function inspect(DatabaseNotification $notification, string $panel, Authenticatable $user): array
    {
        $type = $notification->businessType() ?? NotificationType::System;
        $reference = $this->loadReference($notification);

        if ($notification->reference_type && $notification->reference_id && ! $reference) {
            return ['available' => false, 'url' => null, 'reason' => 'العنصر المرتبط بالإشعار لم يعد متاحاً.'];
        }

        if ($reference && ! $this->canAccessReference($panel, $user, $reference, $type)) {
            return ['available' => false, 'url' => null, 'reason' => 'ليست لديك صلاحية الوصول إلى هذا العنصر.'];
        }

        $url = $this->resolve($notification, $panel, $user);

        return [
            'available' => $url !== null,
            'url' => $url,
            'reason' => $url ? null : 'تعذر تحديد وجهة الإشعار.',
        ];
    }

    protected function loadReference(DatabaseNotification $notification): ?Model
    {
        if (! $notification->reference_type || ! $notification->reference_id) {
            return null;
        }

        if (! class_exists($notification->reference_type)) {
            return null;
        }

        /** @var class-string<Model> $class */
        $class = $notification->reference_type;

        return $class::query()->find($notification->reference_id);
    }

    protected function canAccessReference(string $panel, Authenticatable $user, Model $reference, NotificationType $type): bool
    {
        return match ($panel) {
            'admin' => $this->adminCanAccess($user, $reference, $type),
            'company' => $this->companyCanAccess($user, $reference),
            'center' => $this->centerCanAccess($user, $reference),
            default => false,
        };
    }

    protected function adminCanAccess(Authenticatable $user, Model $reference, NotificationType $type): bool
    {
        $permission = match (true) {
            $reference instanceof Payment => 'payments.view',
            $reference instanceof Booking, $reference instanceof Operation, $reference instanceof EmergencyRequest => 'bookings.view',
            $reference instanceof Complaint => 'complaints.manage',
            $reference instanceof Company => 'companies.view',
            $reference instanceof Center => 'centers.view',
            $reference instanceof Vehicle => 'vehicles.view',
            $reference instanceof Invoice => 'companies.invoices',
            $reference instanceof Subscription => 'companies.view',
            default => 'notifications.view',
        };

        return Permissions::allows($user, $permission) || Permissions::allows($user, 'notifications.view');
    }

    protected function companyCanAccess(Authenticatable $user, Model $reference): bool
    {
        $companyId = Tenant::companyId() ?? ($user->company_id ?? null);

        if (! $companyId) {
            return false;
        }

        return match (true) {
            $reference instanceof Vehicle => (int) $reference->company_id === (int) $companyId,
            $reference instanceof Invoice => (int) $reference->company_id === (int) $companyId,
            $reference instanceof Subscription => (int) $reference->company_id === (int) $companyId,
            $reference instanceof Payment => (int) $reference->company_id === (int) $companyId,
            $reference instanceof Operation => (int) ($reference->vehicle?->company_id
                ?? $reference->vehicle()->value('company_id')) === (int) $companyId,
            $reference instanceof Booking => (int) ($reference->vehicle?->company_id
                ?? $reference->vehicle()->value('company_id')) === (int) $companyId,
            default => false,
        };
    }

    protected function centerCanAccess(Authenticatable $user, Model $reference): bool
    {
        $centerId = Tenant::centerId() ?? ($user->center_id ?? null);

        if (! $centerId) {
            return false;
        }

        return match (true) {
            $reference instanceof Booking => (int) $reference->center_id === (int) $centerId,
            $reference instanceof Operation => (int) $reference->center_id === (int) $centerId,
            $reference instanceof EmergencyRequest => (int) ($reference->center_id ?? 0) === (int) $centerId,
            default => false,
        };
    }

    /**
     * @return array{name: string, params?: array<string, mixed>}|null
     */
    protected function routeFor(string $panel, NotificationType $type, ?Model $reference, DatabaseNotification $notification): ?array
    {
        return match ($panel) {
            'admin' => $this->adminRoute($type, $reference),
            'company' => $this->companyRoute($type, $reference),
            'center' => $this->centerRoute($type, $reference),
            default => null,
        };
    }

    /**
     * @return array{name: string, params?: array<string, mixed>}|null
     */
    protected function adminRoute(NotificationType $type, ?Model $reference): ?array
    {
        if ($reference instanceof Payment) {
            return ['name' => 'admin.payments.show', 'params' => ['payment' => $reference->id]];
        }

        if ($reference instanceof Company) {
            return ['name' => 'admin.companies.show', 'params' => ['company' => $reference->id]];
        }

        if ($reference instanceof Center) {
            return ['name' => 'admin.centers.show', 'params' => ['center' => $reference->id]];
        }

        if ($reference instanceof Vehicle) {
            return ['name' => 'admin.vehicles.show', 'params' => ['vehicle' => $reference->id]];
        }

        if ($reference instanceof Booking) {
            return ['name' => 'admin.requests.index', 'params' => ['tab' => 'bookings', 'q' => $reference->id]];
        }

        if ($reference instanceof Operation) {
            return ['name' => 'admin.requests.index', 'params' => ['tab' => 'operations', 'q' => $reference->id]];
        }

        if ($reference instanceof Complaint) {
            return ['name' => 'admin.requests.index', 'params' => ['tab' => 'complaints', 'q' => $reference->id]];
        }

        if ($reference instanceof EmergencyRequest) {
            return ['name' => 'admin.requests.index', 'params' => ['tab' => 'emergency', 'q' => $reference->id]];
        }

        return match ($type) {
            NotificationType::PaymentPending, NotificationType::PaymentApproved, NotificationType::PaymentRejected => [
                'name' => 'admin.payments.index',
            ],
            NotificationType::BookingNew, NotificationType::BookingStatus => [
                'name' => 'admin.requests.index',
                'params' => ['tab' => 'bookings'],
            ],
            NotificationType::OperationStatus => [
                'name' => 'admin.requests.index',
                'params' => ['tab' => 'operations'],
            ],
            NotificationType::ComplaintNew, NotificationType::ComplaintStatus => [
                'name' => 'admin.requests.index',
                'params' => ['tab' => 'complaints'],
            ],
            NotificationType::EmergencyNew, NotificationType::EmergencyStatus => [
                'name' => 'admin.requests.index',
                'params' => ['tab' => 'emergency'],
            ],
            NotificationType::CompanyCreated => ['name' => 'admin.companies.index'],
            NotificationType::CenterCreated => ['name' => 'admin.centers.index'],
            default => ['name' => 'admin.notifications.index'],
        };
    }

    /**
     * @return array{name: string, params?: array<string, mixed>}|null
     */
    protected function companyRoute(NotificationType $type, ?Model $reference): ?array
    {
        if ($reference instanceof Vehicle) {
            return ['name' => 'company.vehicles.show', 'params' => ['vehicle' => $reference->id]];
        }

        if ($reference instanceof Invoice) {
            return ['name' => 'company.invoices.show', 'params' => ['invoice' => $reference->id]];
        }

        if ($reference instanceof Subscription) {
            return ['name' => 'company.subscription.index'];
        }

        if ($reference instanceof Operation || $type === NotificationType::OperationStatus) {
            return ['name' => 'company.operations.index'];
        }

        if ($reference instanceof Payment) {
            return ['name' => 'company.invoices.index'];
        }

        return match ($type) {
            NotificationType::InvoiceIssued => ['name' => 'company.invoices.index'],
            NotificationType::SubscriptionExpiring => ['name' => 'company.subscription.index'],
            NotificationType::VehicleAssigned => ['name' => 'company.vehicles.index'],
            default => ['name' => 'company.notifications.index'],
        };
    }

    /**
     * @return array{name: string, params?: array<string, mixed>}|null
     */
    protected function centerRoute(NotificationType $type, ?Model $reference): ?array
    {
        if ($reference instanceof Booking || in_array($type, [NotificationType::BookingNew, NotificationType::BookingStatus], true)) {
            return ['name' => 'center.bookings.index'];
        }

        if ($reference instanceof Operation || $type === NotificationType::OperationStatus) {
            return ['name' => 'center.operations.index'];
        }

        return ['name' => 'center.notifications.index'];
    }

    protected function fallback(string $panel): string
    {
        return match ($panel) {
            'admin' => route('admin.notifications.index'),
            'company' => route('company.notifications.index'),
            'center' => route('center.notifications.index'),
            default => '/',
        };
    }
}
