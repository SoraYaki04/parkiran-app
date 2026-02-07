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

class LaporanHarianExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $tanggal;

    public function __construct($tanggal)
    {
        $this->tanggal = Carbon::parse($tanggal);
    }

    public function collection()
    {
        $data = collect();

        // Summary row
        $sessions = ParkirSessions::whereDate('confirmed_at', $this->tanggal)
            ->where('status', 'finished')
            ->count();

        $pembayaran = Pembayaran::whereDate('tanggal_bayar', $this->tanggal)->sum('total_bayar');

        $data->push([
            'Kategori' => 'RINGKASAN',
            'Item' => 'Total Transaksi',
            'Jumlah' => $sessions,
            'Pendapatan' => ''
        ]);

        $data->push([
            'Kategori' => '',
            'Item' => 'Total Pendapatan',
            'Jumlah' => '',
            'Pendapatan' => 'Rp ' . number_format($pembayaran, 0, ',', '.')
        ]);

        $data->push(['Kategori' => '', 'Item' => '', 'Jumlah' => '', 'Pendapatan' => '']);

        // Per Tipe Kendaraan
        $perTipe = ParkirSessions::whereDate('confirmed_at', $this->tanggal)
            ->where('status', 'finished')
            ->select('tipe_kendaraan_id', DB::raw('count(*) as total'))
            ->groupBy('tipe_kendaraan_id')
            ->get();

        $data->push([
            'Kategori' => 'PER TIPE KENDARAAN',
            'Item' => '',
            'Jumlah' => '',
            'Pendapatan' => ''
        ]);

        foreach ($perTipe as $item) {
            $tipe = TipeKendaraan::find($item->tipe_kendaraan_id);
            $pendapatan = Pembayaran::whereDate('tanggal_bayar', $this->tanggal)
                ->whereHas('transaksiParkir', fn($q) => $q->where('tipe_kendaraan_id', $item->tipe_kendaraan_id))
                ->sum('total_bayar');

            $data->push([
                'Kategori' => '',
                'Item' => $tipe ? $tipe->nama_tipe : 'Unknown',
                'Jumlah' => $item->total,
                'Pendapatan' => 'Rp ' . number_format($pendapatan, 0, ',', '.')
            ]);
        }

        $data->push(['Kategori' => '', 'Item' => '', 'Jumlah' => '', 'Pendapatan' => '']);

        // Per Metode Pembayaran
        $perMetode = Pembayaran::whereDate('tanggal_bayar', $this->tanggal)
            ->select('metode_pembayaran', DB::raw('count(*) as total'), DB::raw('sum(total_bayar) as pendapatan'))
            ->groupBy('metode_pembayaran')
            ->get();

        $data->push([
            'Kategori' => 'PER METODE PEMBAYARAN',
            'Item' => '',
            'Jumlah' => '',
            'Pendapatan' => ''
        ]);

        foreach ($perMetode as $item) {
            $data->push([
                'Kategori' => '',
                'Item' => ucfirst($item->metode_pembayaran ?? '-'),
                'Jumlah' => $item->total,
                'Pendapatan' => 'Rp ' . number_format($item->pendapatan, 0, ',', '.')
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Kategori', 'Item', 'Jumlah', 'Pendapatan'];
    }

    public function title(): string
    {
        return 'Laporan ' . $this->tanggal->format('d-m-Y');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
