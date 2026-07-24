<?php

namespace App\Notifications;

use App\Models\MuthowifBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AffiliateReferralBookedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MuthowifBooking $booking,
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
        $code = $this->booking->booking_code ?? $this->booking->id;

        return (new MailMessage)
            ->subject('Referral Anda berhasil booking')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Ada jamaah yang booking menggunakan kode affiliate Anda.')
            ->line('Kode booking: '.$code)
            ->line('Komisi akan masuk sebagai pending setelah pembayaran berhasil, lalu tersedia setelah booking selesai.')
            ->action('Buka Dashboard Affiliate', url('/affiliate'));
    }
}
