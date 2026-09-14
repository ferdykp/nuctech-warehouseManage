<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daily Activity Report Log</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }

        /* HEADER DOKUMEN UTAMA */
        .header {
            text-align: center;
            border-bottom: 2px solid #10b981;
            padding-bottom: 6px;
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
            margin: 2px 0 0 0;
            font-size: 8pt;
            color: #64748b;
            font-weight: bold;
        }

        /* CONTAINER SITE BLOCK */
        .site-block {
            margin-bottom: 12px;
            width: 100%;
        }

        /* CONTAINER SITE HEADER (ABU-ABU) */
        .site-header-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
        }

        .site-header-table td {
            padding: 5px 8px;
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            vertical-align: middle;
        }

        .badge {
            background-color: #10b981;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
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
        }

        .log-entry:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .log-date-box {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: bold;
            font-size: 8pt;
            padding: 2px 6px;
            border: 1px solid #cbd5e1;
            display: inline-block;
            margin-bottom: 4px;
            border-radius: 3px;
        }

        .description {
            font-size: 8.5pt;
            line-height: 1.35;
            color: #334155;
            margin-top: 3px;
        }

        /* GRID FOTO */
        .photo-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
            table-layout: fixed;
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
            max-height: 160px;
            height: auto;
            display: block;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
        }

        .caption {
            font-size: 7pt;
            color: #475569;
            font-style: italic;
            margin-top: 3px;
            font-weight: bold;
        }

        /* TANDA TANGAN / SIGNATURE SECTION (DI KIRI DAN LEBIH RAPAT) */
        .signature-table {
            width: 100%;
            margin-top: 16px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signature-table td {
            font-size: 8.5pt;
            vertical-align: top;
        }

        .signature-title {
            font-weight: bold;
            color: #334155;
            margin-bottom: 25px;
            /* Jarak dibuat lebih rapat */
        }

        .signature-name {
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
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
                    <td style="text-align: right; width: 90px;">
                        <span class="badge">{{ $reports->count() }} Report(s)</span>
                    </td>
                </tr>
            </table>

            {{-- ISI CARD PUTIH --}}
            <div class="site-body">
                @foreach ($reports as $report)
                    <div class="log-entry">
                        <div class="log-date-box">
                            {{ $report->report_date->format('l, d F Y') }}
                        </div>

                        <div class="description">
                            <strong>Log Note:</strong><br>
                            @php
                                // CLEANUP: Membersihkan emoji & simbol non-ASCII yang menyebabkan tanda tanya (?) di DomPDF
                                $cleanDescription = preg_replace('/[^\x20-\x7E\r\n\t]/u', '', $report->description);
                            @endphp
                            {!! nl2br(e($cleanDescription)) !!}
                        </div>

                        {{-- FILTER GAMBAR LOKAL AKTUAL --}}
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
                                    @php
                                        $cleanCaption = preg_replace('/[^\x20-\x7E\r\n\t]/u', '', $photo->caption);
                                    @endphp
                                    <div class="caption">{{ $cleanCaption }}</div>
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

    {{-- BAGIAN TANDA TANGAN (SEBELAH KIRI & LEBIH RAPAT) --}}
    @if ($groupedReports->count() > 0)
        <table class="signature-table">
            <tr>
                <td style="width: 35%; text-align: left;">
                    <div class="signature-title">Knowing,</div>
                    <div class="signature-name">Rangga</div>
                </td>
                <td style="width: 65%;"></td>
            </tr>
        </table>
    @endif

</body>

</html>
