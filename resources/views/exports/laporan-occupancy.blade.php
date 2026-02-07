<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Occupancy</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0; color: #666; }
        .area-card { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; overflow: hidden; }
        .area-header { background: #f5f5f5; padding: 15px; display: flex; justify-content: space-between; }
        .area-name { font-size: 16px; font-weight: bold; }
        .area-location { font-size: 11px; color: #666; }
        .progress-bar { height: 10px; background: #e5e5e5; border-radius: 5px; margin: 10px 15px; }
        .progress-fill { height: 100%; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-center { text-align: center; }
        .text-red { color: #ef4444; }
        .text-green { color: #22c55e; }
        .text-yellow { color: #eab308; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Occupancy</h1>
        <p>Per Tanggal: {{ now()->format('d F Y H:i') }}</p>
    </div>

    @foreach($occupancy as $data)
        <div class="area-card">
            <div class="area-header">
                <div>
                    <div class="area-name">{{ $data['area']->nama_area }}</div>
                    <div class="area-location">{{ $data['area']->lokasi_fisik }}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 18px; font-weight: bold;" class="{{ $data['persentase'] >= 90 ? 'text-red' : ($data['persentase'] >= 70 ? 'text-yellow' : 'text-green') }}">
                        {{ $data['persentase'] }}%
                    </div>
                    <div style="font-size: 11px; color: #666;">{{ $data['terisi'] }} / {{ $data['total_slots'] }} slot</div>
                </div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $data['persentase'] }}%; background: {{ $data['persentase'] >= 90 ? '#ef4444' : ($data['persentase'] >= 70 ? '#eab308' : '#22c55e') }};"></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tipe Kendaraan</th>
                        <th class="text-center">Total Slot</th>
                        <th class="text-center">Terisi</th>
                        <th class="text-center">Kosong</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['per_tipe'] as $tipe)
                        <tr>
                            <td>{{ $tipe['nama_tipe'] }}</td>
                            <td class="text-center">{{ $tipe['total'] }}</td>
                            <td class="text-center text-red">{{ $tipe['terisi'] }}</td>
                            <td class="text-center text-green">{{ $tipe['kosong'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <p style="font-size: 10px; color: #999; text-align: center; margin-top: 30px;">
        Dicetak pada: {{ now()->format('d F Y H:i:s') }}
    </p>
</body>
</html>
