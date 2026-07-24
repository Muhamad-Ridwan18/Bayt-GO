<?php

namespace App\Notifications;

use App\Models\AffiliateWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AffiliateWithdrawalPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AffiliateWithdrawal $withdrawal,
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
        $amount = number_format((float) $this->withdrawal->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject('Withdraw affiliate dibayarkan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Withdraw affiliate Anda telah dibayarkan.')
            ->line('Nominal: Rp '.$amount)
            ->line('Rekening: '.$this->withdrawal->beneficiary_bank.' · '.$this->withdrawal->beneficiary_account)
            ->action('Buka Dashboard Affiliate', url('/affiliate'));
    }
}
