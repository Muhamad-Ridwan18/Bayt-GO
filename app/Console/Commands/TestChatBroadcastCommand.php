<?php

namespace App\Console\Commands;

use App\Models\MuthowifBooking;
use App\Support\BookingChatBroadcast;
use Illuminate\Console\Command;

class TestChatBroadcastCommand extends Command
{
    protected $signature = 'chat:test-broadcast {booking : UUID booking}';

    protected $description = 'Kirim event chat.updated ke Reverb (uji WebSocket di browser)';

    public function handle(): int
    {
        $booking = MuthowifBooking::query()->find($this->argument('booking'));
        if ($booking === null) {
            $this->error('Booking tidak ditemukan.');

            return self::FAILURE;
        }

        $this->line('BROADCAST_CONNECTION: '.(string) config('broadcasting.default'));
        $this->line('Reverb API: '.config('broadcasting.connections.reverb.options.host').':'.config('broadcasting.connections.reverb.options.port'));

        BookingChatBroadcast::notify($booking, 'message', 'cli-test', 'cli');

        $this->info('Event chat.updated dikirim ke channel booking.chat.'.$booking->getKey());
        $this->line('Buka DevTools → WebSocket → harus muncul frame chat.updated.');

        return self::SUCCESS;
    }
}
