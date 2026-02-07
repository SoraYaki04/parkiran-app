<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Harian - {{ $tanggal->format('d F Y') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0; color: #666; }
        .summary { display: flex; margin-bottom: 20px; }
        .summary-box { flex: 1; background: #f5f5f5; padding: 15px; margin: 5px; border-radius: 5px; }
        .summary-box .label { font-size: 11px; color: #666; }
        .summary-box .value { font-size: 18px; font-weight: bold; margin-top: 5px; }
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
        <h1>Laporan Transaksi Harian</h1>
        <p>Tanggal: {{ $tanggal->format('d F Y') }}</p>
    </div>

    <table>
        <tr>
            <td style="width: 50%; background: #f5f5f5;">
                <div class="label">Total Transaksi</div>
                <div class="value">{{ number_format($total_transaksi) }}</div>
            </td>
            <td style="width: 50%; background: #f5f5f5;">
                <div class="label">Total Pendapatan</div>
                <div class="value" style="color: #22c55e;">Rp {{ number_format($total_pendapatan, 0, ',', '.') }}</div>
            </td>
        </tr>
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
