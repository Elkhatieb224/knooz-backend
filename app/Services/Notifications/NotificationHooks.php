<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\Booking;
use App\Models\Center;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\EmergencyRequest;
use App\Models\Operation;
use App\Models\Payment;

class NotificationHooks
{
    public function __construct(protected NotificationService $notifications)
    {
    }

    public function paymentReviewed(Payment $payment, bool $approved, ?string $adminComment = null): void
    {
        $type = $approved ? NotificationType::PaymentApproved : NotificationType::PaymentRejected;
        $title = $approved ? 'تم قبول الدفعة' : 'تم رفض الدفعة';
        $amount = number_format((float) $payment->amount, 2);
        $comment = trim((string) $adminComment);

        if ($approved) {
            $body = "تمت الموافقة على دفعتك بمبلغ {$amount} وإضافة الرصيد إلى المحفظة.";
            if ($comment !== '') {
                $body .= "\nملاحظة الإدارة: {$comment}";
            }
        } else {
            $body = "تم رفض دفعتك بمبلغ {$amount}.";
            $body .= $comment !== ''
                ? "\nالسبب: {$comment}"
                : "\nيرجى التواصل مع الدعم لمزيد من التفاصيل.";
        }

        if ($payment->company_id) {
            $this->notifications->notify(
                $this->notifications->companyUsers((int) $payment->company_id),
                $type,
                $title,
                $body,
                $payment,
                eventKey: sprintf('payment:%d:%s:company', $payment->id, $type->value),
                queue: false,
            );
        }

        if ($payment->user_id && $payment->user) {
            $this->notifications->notify(
                $payment->user,
                $type,
                $title,
                $body,
                $payment,
                eventKey: sprintf('payment:%d:%s:customer', $payment->id, $type->value),
                queue: false,
            );
        }
    }

    public function bookingUpdated(Booking $booking): void
    {
        $status = $booking->status?->label() ?? (string) $booking->status;

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::BookingStatus,
            'تحديث حالة حجز',
            "تم تحديث الحجز #{$booking->id} إلى: {$status}",
            $booking,
            eventKey: sprintf('booking:%d:status:%s:%s', $booking->id, $booking->status?->value ?? 'x', now()->format('YmdHi')),
            queue: true,
        );

        if ($booking->center_id) {
            $this->notifications->notify(
                $this->notifications->centerUsers((int) $booking->center_id),
                NotificationType::BookingStatus,
                'تحديث حالة حجز',
                "تم تحديث الحجز #{$booking->id} إلى: {$status}",
                $booking,
                eventKey: sprintf('booking:%d:center:%s', $booking->id, $booking->status?->value ?? 'x'),
                queue: false,
            );
        }

        $companyId = $booking->vehicle?->company_id ?? $booking->vehicle()->value('company_id');
        if ($companyId) {
            $this->notifications->notify(
                $this->notifications->companyUsers((int) $companyId),
                NotificationType::BookingStatus,
                'تحديث حالة حجز',
                "تم تحديث حجز مرتبط بمركبتكم إلى: {$status}",
                $booking,
                eventKey: sprintf('booking:%d:company:%s', $booking->id, $booking->status?->value ?? 'x'),
                queue: false,
            );
        }

        if ($booking->user_id && $booking->user) {
            $this->notifications->notify(
                $booking->user,
                NotificationType::BookingStatus,
                'تحديث حجزك',
                "تم تحديث حالة حجزك #{$booking->id} إلى: {$status}",
                $booking,
                eventKey: sprintf('booking:%d:customer:%s', $booking->id, $booking->status?->value ?? 'x'),
                queue: false,
            );
        }
    }

    public function operationUpdated(Operation $operation): void
    {
        $status = $operation->status?->label() ?? (string) $operation->status;

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::OperationStatus,
            'تحديث حالة عملية',
            "تم تحديث العملية #{$operation->id} إلى: {$status}",
            $operation,
            eventKey: sprintf('operation:%d:status:%s', $operation->id, $operation->status?->value ?? 'x'),
            queue: true,
        );

        if ($operation->center_id) {
            $this->notifications->notify(
                $this->notifications->centerUsers((int) $operation->center_id),
                NotificationType::OperationStatus,
                'تحديث حالة عملية',
                "تم تحديث العملية #{$operation->id} إلى: {$status}",
                $operation,
                eventKey: sprintf('operation:%d:center:%s', $operation->id, $operation->status?->value ?? 'x'),
                queue: false,
            );
        }

        $companyId = $operation->vehicle?->company_id ?? $operation->vehicle()->value('company_id');
        if ($companyId) {
            $this->notifications->notify(
                $this->notifications->companyUsers((int) $companyId),
                NotificationType::OperationStatus,
                'تحديث عملية',
                "تم تحديث عملية مرتبطة بمركبتكم إلى: {$status}",
                $operation,
                eventKey: sprintf('operation:%d:company:%s', $operation->id, $operation->status?->value ?? 'x'),
                queue: false,
            );
        }

        $customer = $operation->user ?? $operation->vehicle?->user;
        if ($customer) {
            $this->notifications->notify(
                $customer,
                NotificationType::OperationStatus,
                'تحديث عملية',
                "تم تحديث حالة العملية #{$operation->id} إلى: {$status}",
                $operation,
                eventKey: sprintf('operation:%d:customer:%s', $operation->id, $operation->status?->value ?? 'x'),
                queue: false,
            );
        }
    }

    public function complaintUpdated(Complaint $complaint): void
    {
        $this->notifications->notify(
            $this->notifications->activeAdmins('complaints.manage'),
            NotificationType::ComplaintStatus,
            'تحديث شكوى',
            "تم تحديث الشكوى: {$complaint->subject}",
            $complaint,
            eventKey: sprintf('complaint:%d:status:%s', $complaint->id, $complaint->status?->value ?? 'x'),
            queue: false,
        );

        if ($complaint->user_id && $complaint->user) {
            $status = $complaint->status?->label() ?? (string) $complaint->status;
            $this->notifications->notify(
                $complaint->user,
                NotificationType::ComplaintStatus,
                'تحديث شكواك',
                "تم تحديث شكواك «{$complaint->subject}» إلى: {$status}",
                $complaint,
                eventKey: sprintf('complaint:%d:customer:%s', $complaint->id, $complaint->status?->value ?? 'x'),
                queue: false,
            );
        }
    }

    public function emergencyUpdated(EmergencyRequest $emergency): void
    {
        $status = $emergency->status?->label() ?? (string) $emergency->status;
        $isHome = str_starts_with((string) $emergency->notes, 'home:');
        $adminTitle = $isHome ? 'تحديث طلب خدمة منزلية' : 'تحديث طلب طوارئ';
        $customerTitle = $isHome ? 'تحديث طلبك المنزلي' : 'تحديث طلب الطوارئ';

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::EmergencyStatus,
            $adminTitle,
            "تم تحديث الطلب #{$emergency->id} إلى: {$status}",
            $emergency,
            eventKey: sprintf('emergency:%d:status:%s', $emergency->id, $emergency->status?->value ?? 'x'),
            queue: false,
        );

        if ($emergency->user_id && $emergency->user) {
            $this->notifications->notify(
                $emergency->user,
                NotificationType::EmergencyStatus,
                $customerTitle,
                "تم تحديث طلبك #{$emergency->id} إلى: {$status}",
                $emergency,
                eventKey: sprintf('emergency:%d:customer:%s', $emergency->id, $emergency->status?->value ?? 'x'),
                queue: false,
            );
        }
    }

    public function companyCreated(Company $company): void
    {
        $this->notifications->notify(
            $this->notifications->activeAdmins('companies.view'),
            NotificationType::CompanyCreated,
            'شركة جديدة',
            "تم تسجيل الشركة: {$company->name}",
            $company,
            eventKey: sprintf('company:%d:created', $company->id),
            queue: false,
        );
    }

    public function centerCreated(Center $center): void
    {
        $this->notifications->notify(
            $this->notifications->activeAdmins('centers.view'),
            NotificationType::CenterCreated,
            'مركز جديد',
            "تم تسجيل المركز: {$center->name}",
            $center,
            eventKey: sprintf('center:%d:created', $center->id),
            queue: false,
        );
    }
}
