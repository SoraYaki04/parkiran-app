<?php

namespace App\Http\Controllers;

use App\Exports\LaporanHarianExport;
use App\Exports\LaporanRentangExport;
use App\Models\ParkirSessions;
use App\Models\Pembayaran;
use App\Models\TipeKendaraan;
use App\Models\AreaParkir;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanExportController extends Controller
{
    // ==================== EXCEL EXPORTS ====================

    public function excelHarian(Request $request)
    {
        $tanggal = $request->get('tanggal', now()->format('Y-m-d'));
        $filename = 'laporan-harian-' . $tanggal . '.xlsx';

        return Excel::download(new LaporanHarianExport($tanggal), $filename);
    }

    public function excelRentang(Request $request)
    {
        $mulai = $request->get('mulai', now()->subDays(7)->format('Y-m-d'));
        $akhir = $request->get('akhir', now()->format('Y-m-d'));
        $filename = 'laporan-' . $mulai . '-sampai-' . $akhir . '.xlsx';

        return Excel::download(new LaporanRentangExport($mulai, $akhir), $filename);
    }

    // ==================== PDF EXPORTS ====================

    public function pdfHarian(Request $request)
    {
        $tanggal = Carbon::parse($request->get('tanggal', now()->format('Y-m-d')));

        $sessions = ParkirSessions::whereDate('confirmed_at', $tanggal)
            ->where('status', 'finished')
            ->get();

        $pembayaran = Pembayaran::whereDate('tanggal_bayar', $tanggal)->get();

        $perTipe = ParkirSessions::whereDate('confirmed_at', $tanggal)
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get()
            ->map(function ($item) use ($tanggal) {
                $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
                $pendapatan = Pembayaran::whereDate('tanggal_bayar', $tanggal)
                    ->whereHas('transaksiParkir', fn($q) => $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id))
                    ->sum('total_bayar');
                $item->tipeKendaraan = $tipe;
                $item->pendapatan = $pendapatan;
                return $item;
            });

        $perMetode = Pembayaran::whereDate('tanggal_bayar', $tanggal)
            ->select('metode_pembayaran', DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy('metode_pembayaran')
            ->get();

        $data = [
            'tanggal' => $tanggal,
            'total_transaksi' => $sessions->count(),
            'total_pendapatan' => $pembayaran->sum('total_bayar'),
            'per_tipe' => $perTipe,
            'per_metode' => $perMetode,
        ];

        $pdf = Pdf::loadView('exports.laporan-harian', $data);
        return $pdf->download('laporan-harian-' . $tanggal->format('Y-m-d') . '.pdf');
    }

    public function pdfRentang(Request $request)
    {
        $mulai = Carbon::parse($request->get('mulai', now()->subDays(7)->format('Y-m-d')))->startOfDay();
        $akhir = Carbon::parse($request->get('akhir', now()->format('Y-m-d')))->endOfDay();

        $sessions = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->get();

        $pembayaran = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])->get();

        $perTipe = ParkirSessions::whereBetween('confirmed_at', [$mulai, $akhir])
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get()
            ->map(function ($item) use ($mulai, $akhir) {
                $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
                $pendapatan = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
                    ->whereHas('transaksiParkir', fn($q) => $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id))
                    ->sum('total_bayar');
                $item->tipeKendaraan = $tipe;
                $item->pendapatan = $pendapatan;
                return $item;
            });

        $perMetode = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
            ->select('metode_pembayaran', DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy('metode_pembayaran')
            ->get();

        $perHari = Pembayaran::whereBetween('tanggal_bayar', [$mulai, $akhir])
            ->select(DB::raw('DATE(tanggal_bayar) as tanggal'), DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy(DB::raw('DATE(tanggal_bayar)'))
            ->orderBy('tanggal')
            ->get();

        $data = [
            'mulai' => $mulai,
            'akhir' => $akhir,
            'total_transaksi' => $sessions->count(),
            'total_pendapatan' => $pembayaran->sum('total_bayar'),
            'per_tipe' => $perTipe,
            'per_metode' => $perMetode,
            'per_hari' => $perHari,
        ];

        $pdf = Pdf::loadView('exports.laporan-rentang', $data);
        return $pdf->download('laporan-' . $mulai->format('Y-m-d') . '-sampai-' . $akhir->format('Y-m-d') . '.pdf');
    }

    public function pdfOccupancy()
    {
        $areas = AreaParkir::with(['slots', 'kapasitas.tipeKendaraan'])->get();

        $occupancy = [];
        foreach ($areas as $area) {
            $totalSlots = $area->slots->count();
            $terisi = $area->slots->where('status', 'terisi')->count();
            $kosong = $area->slots->where('status', 'kosong')->count();
            $persentase = $totalSlots > 0 ? round(($terisi / $totalSlots) * 100, 1) : 0;

            $perTipe = $area->slots->groupBy('tipe_kendaraan_id')->map(function ($slots, $tipeId) {
                $tipe = TipeKendaraan::find($tipeId);
                return [
                    'nama_tipe' => $tipe ? $tipe->nama_tipe : 'Unknown',
                    'total' => $slots->count(),
                    'terisi' => $slots->where('status', 'terisi')->count(),
                    'kosong' => $slots->where('status', 'kosong')->count(),
                ];
            });

            $occupancy[] = [
                'area' => $area,
                'total_slots' => $totalSlots,
                'terisi' => $terisi,
                'kosong' => $kosong,
                'persentase' => $persentase,
                'per_tipe' => $perTipe,
            ];
        }

        $pdf = Pdf::loadView('exports.laporan-occupancy', ['occupancy' => $occupancy]);
        return $pdf->download('laporan-occupancy-' . now()->format('Y-m-d-His') . '.pdf');
    }
}
