<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daily Activity Report Log</title>
    <style>
        @page {
            margin: 15mm;
            /* Atur margin cetak kertas */
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            margin: 0;
            padding: 12px;
            background-color: #ffffff;
            line-height: 1.35;
        }

        .header {
            text-align: center;
            border-bottom: 1.5px solid #10b981;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.3px;
        }

        .header p {
            margin: 2px 0 0;
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
        }

        .site-block {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .site-header {
            background-color: #e2e8f0;
            border: 1px solid #cbd5e1;
            border-radius: 6px 6px 0 0;
            padding: 6px 10px;
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.2px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .site-header span.badge {
            background-color: #10b981;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 700;
            padding: 1.5px 7px;
            border-radius: 10px;
        }

        .site-body {
            border: 1px solid #cbd5e1;
            border-top: none;
            border-radius: 0 0 6px 6px;
            padding: 8px 10px;
            background-color: #ffffff;
        }

        .log-entry {
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 8px;
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
            font-size: 9px;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            margin-bottom: 4px;
        }

        .description {
            font-size: 9.5px;
            line-height: 1.4;
            white-space: pre-line;
            color: #334155;
            margin-top: 2px;
        }

        .photo-grid {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .photo-item {
            width: 100%;
            max-width: 320px;
            box-sizing: border-box;
            text-align: center;
            background-color: #f8fafc;
            padding: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .photo-item img {
            max-width: 100%;
            max-height: 220px;
            height: auto;
            width: auto;
            object-fit: contain;
            border-radius: 3px;
            border: 1px solid #cbd5e1;
            display: block;
            margin: 0 auto;
        }

        .caption {
            font-size: 8px;
            color: #475569;
            font-style: italic;
            margin-top: 3px;
            font-weight: 600;
        }

        .no-print-bar {
            background: #0f172a;
            color: white;
            padding: 8px 14px;
            margin: -12px -12px 12px -12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
        }

        .btn-print {
            background-color: #10b981;
            color: white;
            border: none;
            padding: 4px 12px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
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
            <div class="site-header">
                <div>
                    📍 SITE: {{ strtoupper($siteName) }} (BRANCH: {{ strtoupper($branchName) }})
                </div>
                <span class="badge">{{ $reports->count() }} Report(s)</span>
            </div>

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

                        {{-- FILTER APABILA FILE GAMBAR AKTUAL BENAR-BENAR ADA --}}
                        @php
                            $validPhotos = $report->photos->filter(function ($photo) {
                                return !empty($photo->photo_path) &&
                                    file_exists(public_path('storage/' . $photo->photo_path));
                            });
                        @endphp

                        @if ($validPhotos->count() > 0)
                            <div class="photo-grid">
                                @foreach ($validPhotos as $photo)
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
        <p style="text-align: center; color: #94a3b8; padding: 20px; font-weight: bold;">
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
