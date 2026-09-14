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

        .site-block {
            margin-bottom: 30px;
            page-break-after: always;
            /* Tiap site otomatis beda halaman ketika dicetak */
        }

        .site-block:last-child {
            page-break-after: auto;
        }

        /* HEADER KETERANGAN SITE (WARNA ABU-ABU) */
        .site-header {
            background-color: #e2e8f0;
            border: 1px solid #cbd5e1;
            border-radius: 10px 10px 0 0;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .site-header span.badge {
            background-color: #10b981;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 12px;
        }

        /* CONTAINER KARTU PUTIH UNTUK TANGGAL & LOG NOTE */
        .site-body {
            border: 1px solid #cbd5e1;
            border-top: none;
            border-radius: 0 0 10px 10px;
            padding: 16px;
            background-color: #ffffff;
        }

        .log-entry {
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }

        .log-entry:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .log-date {
            display: inline-block;
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            margin-bottom: 8px;
        }

        .description {
            font-size: 11px;
            line-height: 1.6;
            white-space: pre-line;
            color: #334155;
            margin-top: 4px;
        }

        .photo-grid {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .photo-item {
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            text-align: center;
            background-color: #f8fafc;
            padding: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .photo-item img {
            max-width: 100%;
            max-height: 300px;
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
            margin-top: 4px;
            font-weight: 600;
        }

        .no-print-bar {
            background: #0f172a;
            color: white;
            padding: 10px 20px;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-print {
            background-color: #10b981;
            color: white;
            border: none;
            padding: 6px 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="no-print-bar">
        <span>📄 Daily Activity Report - PDF Mode</span>
        <button onclick="window.print()" class="btn-print">🖨️ Cetak / Save PDF</button>
    </div>

    <div class="header">
        <h2>Daily Activity Report Log</h2>
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} -
            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>

    @forelse($groupedReports as $siteId => $reports)
        @php
            $firstReport = $reports->first();
            $siteName = $firstReport->site->machine_name ?? 'Unknown Site';
            $branchName = $firstReport->site->branch->branch_name ?? '-';
        @endphp

        <div class="site-block">
            {{-- HEADER WARNA ABU-ABU: HANYA MENAMPILKAN KETERANGAN SITE --}}
            <div class="site-header">
                <div>
                    📍 SITE: {{ strtoupper($siteName) }} (BRANCH: {{ strtoupper($branchName) }})
                </div>
                <span class="badge">{{ $reports->count() }} Report(s)</span>
            </div>

            {{-- ISINYA BERWARNA PUTIH: TANGGAL & LOG NOTE --}}
            <div class="site-body">
                @foreach ($reports as $report)
                    <div class="log-entry">
                        <div class="log-date">
                            📅 {{ $report->report_date->format('l, d F Y') }}
                        </div>

                        <div class="description">
                            <strong>Log Note:</strong><br>
                            {{ $report->description }}
                        </div>

                        @if ($report->photos->count() > 0)
                            <div class="photo-grid">
                                @foreach ($report->photos as $photo)
                                    <div class="photo-item">
                                        <img src="{{ asset('storage/' . $photo->photo_path) }}"
                                            alt="Photo Documentation">
                                        @if ($photo->caption)
                                            <div class="caption">{{ $photo->caption }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p style="text-align: center; color: #94a3b8; padding: 30px; font-weight: bold;">
            Tidak ada catatan kegiatan harian untuk rentang tanggal ini.
        </p>
    @endforelse

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>

</html>
