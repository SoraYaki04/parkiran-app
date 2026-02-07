<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $mulai->format('d M Y') }} - {{ $akhir->format('d M Y') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Transaksi</h1>
        <p>Periode: {{ $mulai->format('d F Y') }} - {{ $akhir->format('d F Y') }}</p>
    </div>

    <table>
        <tr>
            <td style="width: 50%; background: #f5f5f5;">
                <div>Total Transaksi</div>
                <div style="font-size: 18px; font-weight: bold;">{{ number_format($total_transaksi) }}</div>
            </td>
            <td style="width: 50%; background: #f5f5f5;">
                <div>Total Pendapatan</div>
                <div style="font-size: 18px; font-weight: bold; color: #22c55e;">Rp {{ number_format($total_pendapatan, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Breakdown Per Hari</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="text-center">Jumlah Transaksi</th>
                <th class="text-right">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($per_hari as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</td>
                    <td class="text-center">{{ $item->total }}</td>
                    <td class="text-right">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Per Tipe Kendaraan</div>
    <table>
        <thead>
            <tr>
                <th>Tipe</th>
                <th class="text-center">Jumlah</th>
                <th class="text-right">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($per_tipe as $item)
                <tr>
                    <td>{{ $item->tipeKendaraan->nama_tipe ?? '-' }}</td>
                    <td class="text-center">{{ $item->total }}</td>
                    <td class="text-right">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Per Metode Pembayaran</div>
    <table>
        <thead>
            <tr>
                <th>Metode</th>
                <th class="text-center">Jumlah</th>
                <th class="text-right">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($per_metode as $item)
                <tr>
                    <td>{{ ucfirst($item->metode_pembayaran ?? '-') }}</td>
                    <td class="text-center">{{ $item->total }}</td>
                    <td class="text-right">Rp {{ number_format($item->pendapatan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="font-size: 10px; color: #999; text-align: center; margin-top: 30px;">
        Dicetak pada: {{ now()->format('d F Y H:i:s') }}
    </p>
</body>
</html>
