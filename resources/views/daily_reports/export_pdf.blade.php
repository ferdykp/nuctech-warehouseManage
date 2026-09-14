<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daily Activity Report Log</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 10mm 12mm 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }

        /* HEADER DOKUMEN */
        .header {
            text-align: center;
            border-bottom: 1.5px solid #10b981;
            padding-bottom: 4px;
            margin-bottom: 12px;
        }

        .header h2 {
            margin: 0;
            font-size: 13pt;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 3px 0 0 0;
            font-size: 8.5pt;
            color: #64748b;
            font-weight: bold;
        }

        /* SITE CARD (CONTINUE LAYOUT) */
        .site-block {
            margin-bottom: 14px;
            width: 100%;
        }

        /* CONTAINER SITE HEADER (ABU-ABU) */
        .site-header-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #e2e8f0;
            border: 1px solid #cbd5e1;
        }

        .site-header-table td {
            padding: 5px 8px;
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
        }

        .badge {
            background-color: #10b981;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 8px;
            display: inline-block;
        }

        /* CONTAINER ISI LOG ACTIVITY (KARTU PUTIH) */
        .site-body {
            border: 1px solid #cbd5e1;
            border-top: none;
            padding: 8px;
            background-color: #ffffff;
        }

        .log-entry {
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 8px;
            margin-bottom: 8px;
            page-break-inside: avoid;
            /* Menjaga agar 1 log entry tidak terpotong di tengah halaman */
        }

        .log-entry:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .log-date-box {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 2px 6px;
            border: 1px solid #cbd5e1;
            display: inline-block;
            margin-bottom: 4px;
        }

        .description {
            font-size: 8.5pt;
            line-height: 1.35;
            color: #334155;
            margin-top: 3px;
        }

        /* TANYA & TAMPILKAN FOTO MENGGUNAKAN TABLE BROWSER DOMPDF */
        .photo-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
        }

        .photo-td {
            width: 50%;
            padding: 3px;
            vertical-align: top;
            box-sizing: border-box;
        }

        .photo-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 4px;
            text-align: center;
        }

        .photo-box img {
            max-width: 100%;
            max-height: 180px;
            height: auto;
            display: block;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
        }

        .caption {
            font-size: 7.5pt;
            color: #475569;
            font-style: italic;
            margin-top: 3px;
            font-weight: bold;
        }
    </style>
</head>

<body>

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
            {{-- HEADER WARNA ABU-ABU --}}
            <table class="site-header-table">
                <tr>
                    <td style="text-align: left;">
                        SITE: {{ strtoupper($siteName) }} (BRANCH: {{ strtoupper($branchName) }})
                    </td>
                    <td style="text-align: right; width: 100px;">
                        <span class="badge">{{ $reports->count() }} Report(s)</span>
                    </td>
                </tr>
            </table>

            {{-- ISI CARD KARTU PUTIH (CONTINUE) --}}
            <div class="site-body">
                @foreach ($reports as $report)
                    <div class="log-entry">
                        <div class="log-date-box">
                            {{ $report->report_date->format('l, d F Y') }}
                        </div>

                        <div class="description">
                            <strong>Log Note:</strong><br>
                            {!! nl2br(e($report->description)) !!}
                        </div>

                        {{-- FILTER APABILA FILE GAMBAR FISIK AKTUAL ADA --}}
                        @php
                            $validPhotos = $report->photos->filter(function ($photo) {
                                return !empty($photo->photo_path) &&
                                    file_exists(public_path('storage/' . $photo->photo_path));
                            });
                        @endphp

                        @if ($validPhotos->count() > 0)
                            <table class="photo-table">
                                <tr>
                                    @foreach ($validPhotos as $index => $photo)
                                        @if ($index > 0 && $index % 2 == 0)
                                </tr>
                                <tr>
                        @endif
                        <td class="photo-td">
                            <div class="photo-box">
                                <img src="{{ public_path('storage/' . $photo->photo_path) }}" alt="Photo Documentation">
                                @if ($photo->caption)
                                    <div class="caption">{{ $photo->caption }}</div>
                                @endif
                            </div>
                        </td>
                @endforeach
                @if ($validPhotos->count() % 2 != 0)
                    <td class="photo-td"></td>
                @endif
                </tr>
                </table>
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

</body>

</html>
