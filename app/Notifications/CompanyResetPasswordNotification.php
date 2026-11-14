<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('إعادة تعيين كلمة مرور حساب الشركة')
            ->greeting('مرحباً '.$notifiable->name)
            ->line('وصلنا طلب لإعادة تعيين كلمة المرور لحسابك في تطبيق الشركات.')
            ->line('رمز إعادة التعيين:')
            ->line($this->token)
            ->line('ينتهي صلاحية الرمز خلال ساعة واحدة.')
            ->line('إذا لم تطلب إعادة التعيين، يمكنك تجاهل هذه الرسالة.');
    }
}
