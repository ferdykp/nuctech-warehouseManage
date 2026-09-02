<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daily Activity Report Log</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #10b981;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 6px 10px;
            font-size: 11px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .report-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-bottom: 16px;
            padding: 14px;
            page-break-inside: avoid;
            background-color: #ffffff;
        }

        .report-header {
            background-color: #f1f5f9;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #cbd5e1;
            margin: -14px -14px 12px -14px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            color: #0f172a;
        }

        .description {
            font-size: 11px;
            line-height: 1.5;
            white-space: pre-line;
            margin-bottom: 12px;
            color: #334155;
        }

        .photo-grid {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .photo-item {
            width: 100%;
            max-width: 450px;
            margin: 0 auto 10px auto;
            box-sizing: border-box;
            text-align: center;
            background-color: #f8fafc;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        /* PERBAIKAN: Menggunakan object-fit: contain dan height auto agar gambar utuh */
        .photo-item img {
            max-width: 100%;
            max-height: 350px;
            height: auto;
            width: auto;
            object-fit: contain;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            display: block;
            margin: 0 auto;
        }

        .caption {
            font-size: 9px;
            color: #475569;
            font-style: italic;
            margin-top: 6px;
            font-weight: 600;
        }

        @media print {
            body {
                padding: 0;
            }

            .report-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Daily Activity Report</h2>
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} -
            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Site Location:</strong> {{ $site ? $site->machine_name : 'All Registered Sites' }}</td>
            <td style="text-align: right;"><strong>Total Entries:</strong> {{ $reports->count() }} Record(s)</td>
        </tr>
    </table>

    @forelse($reports as $report)
        <div class="report-card">
            <div class="report-header">
                📅 {{ $report->report_date->format('l, d F Y') }}
                &bull; Site: {{ $report->site->machine_name ?? '-' }}
                &bull; Reporter: {{ $report->user->name ?? '-' }}
            </div>

            <div class="description">
                <strong>Log Note:</strong><br>
                {{ $report->description }}
            </div>

            @if ($report->photos->count() > 0)
                <div class="photo-grid">
                    @foreach ($report->photos as $photo)
                        <div class="photo-item">
                            <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo Documentation">
                            @if ($photo->caption)
                                <div class="caption">{{ $photo->caption }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p style="text-align: center; color: #94a3b8; padding: 30px; font-weight: bold;">
            Tidak ada catatan kegiatan harian untuk rentang tanggal ini.
        </p>
    @endforelse

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>

</html>
