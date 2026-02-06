<?php

namespace App\Exports;

use App\Models\ParkirSessions;
use App\Models\Pembayaran;
use App\Models\TipeKendaraan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanRentangExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $mulai;
    protected $akhir;

    public function __construct($mulai, $akhir)
    {
        $this->mulai = Carbon::parse($mulai)->startOfDay();
        $this->akhir = Carbon::parse($akhir)->endOfDay();
    }

    public function collection()
    {
        $data = collect();

        // Summary
        $sessions = ParkirSessions::whereBetween('confirmed_at', [$this->mulai, $this->akhir])
            ->where('status', 'finished')
            ->count();

        $pembayaran = Pembayaran::whereBetween('tanggal_bayar', [$this->mulai, $this->akhir])->sum('total_bayar');

        $data->push([
            'Tanggal' => 'RINGKASAN',
            'Keterangan' => 'Total Transaksi: ' . $sessions,
            'Jumlah' => '',
            'Pendapatan' => 'Rp ' . number_format($pembayaran, 0, ',', '.')
        ]);

        $data->push(['Tanggal' => '', 'Keterangan' => '', 'Jumlah' => '', 'Pendapatan' => '']);

        // Per Hari
        $data->push([
            'Tanggal' => 'BREAKDOWN PER HARI',
            'Keterangan' => '',
            'Jumlah' => '',
            'Pendapatan' => ''
        ]);

        $perHari = Pembayaran::whereBetween('tanggal_bayar', [$this->mulai, $this->akhir])
            ->select(DB::raw('DATE(tanggal_bayar) as tanggal'), DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy(DB::raw('DATE(tanggal_bayar)'))
            ->orderBy('tanggal')
            ->get();

        foreach ($perHari as $item) {
            $data->push([
                'Tanggal' => Carbon::parse($item->tanggal)->format('d M Y'),
                'Keterangan' => '',
                'Jumlah' => $item->total,
                'Pendapatan' => 'Rp ' . number_format($item->pendapatan, 0, ',', '.')
            ]);
        }

        $data->push(['Tanggal' => '', 'Keterangan' => '', 'Jumlah' => '', 'Pendapatan' => '']);

        // Per Tipe Kendaraan
        $data->push([
            'Tanggal' => 'PER TIPE KENDARAAN',
            'Keterangan' => '',
            'Jumlah' => '',
            'Pendapatan' => ''
        ]);

        $perTipe = ParkirSessions::whereBetween('confirmed_at', [$this->mulai, $this->akhir])
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get();

        foreach ($perTipe as $item) {
            $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
            $pendapatan = Pembayaran::whereBetween('tanggal_bayar', [$this->mulai, $this->akhir])
                ->whereHas('transaksiParkir', fn($q) => $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id))
                ->sum('total_bayar');

            $data->push([
                'Tanggal' => '',
                'Keterangan' => $tipe ? $tipe->nama_tipe : 'Unknown',
                'Jumlah' => $item->total,
                'Pendapatan' => 'Rp ' . number_format($pendapatan, 0, ',', '.')
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Tanggal', 'Keterangan', 'Jumlah', 'Pendapatan'];
    }

    public function title(): string
    {
        return 'Laporan ' . $this->mulai->format('d-m-Y') . ' - ' . $this->akhir->format('d-m-Y');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
