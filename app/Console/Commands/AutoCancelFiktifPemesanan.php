<?php

namespace App\Console\Commands;

use App\Services\PemesananStatusService;
use Illuminate\Console\Command;

class AutoCancelFiktifPemesanan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pemesanan:auto-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan secara otomatis pemesanan Pending yang belum diverifikasi admin setelah 3 hari.';

    /**
     * Execute the console command.
     */
    public function handle(PemesananStatusService $statusService): int
    {
        $count = $statusService->autoCancelExpired();

        $this->info("Berhasil membatalkan {$count} pemesanan yang kedaluwarsa secara otomatis.");
        $this->line("Successfully cancelled {$count} expired/fake bookings.");

        return Command::SUCCESS;
    }
}
