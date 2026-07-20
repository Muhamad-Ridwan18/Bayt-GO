<?php

namespace App\Notifications;

use App\Models\AffiliateCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AffiliateCommissionAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AffiliateCommission $commission,
    ) {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->commission->commission_amount, 0, ',', '.');

        return (new MailMessage)
            ->subject('Komisi affiliate tersedia')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Komisi affiliate Anda sudah ditambahkan ke saldo.')
            ->line('Nominal: Rp '.$amount)
            ->action('Lihat Saldo', url('/affiliate'));
    }
}
