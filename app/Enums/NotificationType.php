<?php

namespace App\Enums;

enum NotificationType: string
{
    case System = 'system';
    case BookingNew = 'booking.new';
    case BookingStatus = 'booking.status';
    case OperationStatus = 'operation.status';
    case PaymentPending = 'payment.pending';
    case PaymentApproved = 'payment.approved';
    case PaymentRejected = 'payment.rejected';
    case ComplaintNew = 'complaint.new';
    case ComplaintStatus = 'complaint.status';
    case EmergencyNew = 'emergency.new';
    case EmergencyStatus = 'emergency.status';
    case CompanyCreated = 'company.created';
    case CenterCreated = 'center.created';
    case SubscriptionExpiring = 'subscription.expiring';
    case InvoiceIssued = 'invoice.issued';
    case VehicleAssigned = 'vehicle.assigned';
    case AdminAction = 'admin.action';

    public function label(): string
    {
        return match ($this) {
            self::System => 'نظام',
            self::BookingNew => 'حجز جديد',
            self::BookingStatus => 'تحديث حجز',
            self::OperationStatus => 'تحديث عملية',
            self::PaymentPending => 'دفعة قيد المراجعة',
            self::PaymentApproved => 'قبول دفعة',
            self::PaymentRejected => 'رفض دفعة',
            self::ComplaintNew => 'شكوى جديدة',
            self::ComplaintStatus => 'تحديث شكوى',
            self::EmergencyNew => 'طلب طوارئ',
            self::EmergencyStatus => 'تحديث طوارئ',
            self::CompanyCreated => 'شركة جديدة',
            self::CenterCreated => 'مركز جديد',
            self::SubscriptionExpiring => 'اشتراك ينتهي',
            self::InvoiceIssued => 'فاتورة',
            self::VehicleAssigned => 'مركبة',
            self::AdminAction => 'إجراء إداري',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::System => 'bi-bell',
            self::BookingNew, self::BookingStatus => 'bi-calendar-check',
            self::OperationStatus => 'bi-gear',
            self::PaymentPending, self::PaymentApproved, self::PaymentRejected => 'bi-cash-coin',
            self::ComplaintNew, self::ComplaintStatus => 'bi-chat-left-text',
            self::EmergencyNew, self::EmergencyStatus => 'bi-exclamation-triangle',
            self::CompanyCreated => 'bi-building',
            self::CenterCreated => 'bi-geo-alt',
            self::SubscriptionExpiring => 'bi-hourglass-split',
            self::InvoiceIssued => 'bi-receipt',
            self::VehicleAssigned => 'bi-car-front',
            self::AdminAction => 'bi-shield-check',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
