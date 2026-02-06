<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParkirSessions;
use App\Models\SlotParkir;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredParkir extends Command
{
    protected $signature = 'parkir:release-expired';
    protected $description = 'Release slot parkir dari session yang expired';

    public function handle()
    {
        DB::transaction(function () {

            $sessions = ParkirSessions::whereIn('status', [
                    'WAITING_INPUT',
                    'SCANNED'
                ])
                ->whereNotNull('expired_at')
                ->where('expired_at', '<', now())
                ->get();

            foreach ($sessions as $session) {

                // Lepas slot jika ada
                if ($session->slot_parkir_id) {
                    SlotParkir::where('id', $session->slot_parkir_id)
                        ->update(['status' => 'kosong']);
                }

                // Cancel session
                $session->update([
                    'status' => 'CANCELLED'
                ]);
            }
        });

        $this->info('Expired parkir session released.');
    }
}
