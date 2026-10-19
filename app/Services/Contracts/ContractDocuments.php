<?php

namespace App\Services\Contracts;

use App\Models\ContractTemplate;
use App\Models\EmployeeContract;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use ZipArchive;

class ContractDocuments
{
    public const REQUIRED = ['contract_number', 'employee_name', 'employee_nik', 'employee_address', 'employee_phone', 'contract_day', 'contract_day_number', 'contract_month', 'contract_year', 'start_date', 'end_date', 'salary', 'position', 'job_type', 'work_location', 'working_hours', 'leave_entitlement', 'representative_name', 'representative_position', 'representative_address'];

    public function defaultTemplate(): ContractTemplate
    {
        $path = resource_path('contracts/pkwt-default-v1.docx');

        return ContractTemplate::firstOrCreate(['version' => 'resmi-v1'], ['name' => 'PKWT Unilogistik / Nuctech', 'path' => 'builtin:pkwt-default-v1.docx', 'sha256' => hash_file('sha256', $path)]);
    }

    public function templatePath(ContractTemplate $template): string
    {
        return $template->path === 'builtin:pkwt-default-v1.docx' ? resource_path('contracts/pkwt-default-v1.docx') : Storage::disk('local')->path($template->path);
    }

    public function validateTemplate(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['template' => 'File bukan DOCX yang valid.']);
        }
        try {
            $size = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $s = $zip->statIndex($i);
                $size += $s['size'];
                if ($size > 30 * 1024 * 1024 || str_contains($s['name'], '..') || str_starts_with($s['name'], '/') || preg_match('/vbaProject|embeddings\//i', $s['name'])) {
                    throw ValidationException::withMessages(['template' => 'Template terlalu besar atau mengandung objek yang tidak didukung.']);
                }
                if (str_ends_with($s['name'], '.rels') && preg_match('/TargetMode\s*=\s*["\']External/i', $zip->getFromIndex($i))) {
                    throw ValidationException::withMessages(['template' => 'Template tidak boleh memuat referensi eksternal.']);
                }
            }
            $xml = $zip->getFromName('word/document.xml');
            if (! $xml) {
                throw ValidationException::withMessages(['template' => 'Struktur dokumen tidak lengkap.']);
            }
            $dom = new DOMDocument;
            if (! @$dom->loadXML($xml, LIBXML_NONET)) {
                throw ValidationException::withMessages(['template' => 'XML dokumen tidak valid.']);
            }
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $text = '';
            foreach ($xpath->query('//w:t') as $node) {
                $text .= $node->textContent;
            }
            foreach (self::REQUIRED as $key) {
                if (! str_contains($text, '${'.$key.'}')) {
                    throw ValidationException::withMessages(['template' => 'Placeholder wajib belum ada: ${'.$key.'}.']);
                }
            }
            preg_match_all('/\$\{([^}]+)\}/', $text, $matches);
            if (array_diff($matches[1], self::REQUIRED)) {
                throw ValidationException::withMessages(['template' => 'Template berisi placeholder yang tidak dikenal. Gunakan daftar kolom yang didukung.']);
            }
        } finally {
            $zip->close();
        }
    }

    public function values(EmployeeContract $contract): array
    {
        $date = $contract->contract_date->copy()->locale('id');
        $s = $contract->snapshot;

        return array_merge($s, [
            'contract_number' => $contract->contract_number, 'contract_day' => $date->translatedFormat('l'),
            'contract_day_number' => $date->format('d'), 'contract_month' => $date->translatedFormat('F'), 'contract_year' => $date->format('Y'),
            'start_date' => $contract->start_date->copy()->locale('id')->translatedFormat('d F Y'),
            'end_date' => $contract->end_date->copy()->locale('id')->translatedFormat('d F Y'),
            'salary' => 'Rp '.number_format((float) $s['basic_salary'], 2, ',', '.'),
        ]);
    }

    public function generate(EmployeeContract $contract): array
    {
        $template = $contract->template;
        $source = $this->templatePath($template);
        abort_unless(is_file($source) && hash_equals($template->sha256, hash_file('sha256', $source)), 422, 'Template berubah atau hilang. Unggah sebagai versi baru.');
        $this->validateTemplate($source);
        $disk = Storage::disk('local');
        $directory = 'contracts/'.$contract->id.'/'.Str::uuid();
        $disk->makeDirectory($directory);
        $docx = $directory.'/kontrak.docx';
        abort_unless($disk->put($docx, file_get_contents($source)), 500, 'Dokumen gagal disimpan.');
        $path = $disk->path($docx);
        $zip = new ZipArchive;
        $zip->open($path);
        $values = $this->values($contract);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! preg_match('~^word/(document|header\d+|footer\d+)\.xml$~', $name)) {
                continue;
            }
            $dom = new DOMDocument;
            $dom->loadXML($zip->getFromIndex($i), LIBXML_NONET);
            $xp = new DOMXPath($dom);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            foreach ($xp->query('//w:p') as $p) {
                $nodes = iterator_to_array($xp->query('.//w:t', $p));
                $joined = implode('', array_map(fn ($n) => $n->textContent, $nodes));
                if (! str_contains($joined, '${')) {
                    continue;
                }
                // Replace backwards to keep offsets stable even when Word splits a token across runs.
                preg_match_all('/\$\{([^}]+)\}/', $joined, $matches, PREG_OFFSET_CAPTURE);
                foreach (array_reverse($matches[0], true) as $k => $match) {
                    $start = $match[1];
                    $end = $start + strlen($match[0]);
                    $offset = 0;
                    $inserted = false;
                    $key = $matches[1][$k][0];
                    $replacement = (string) ($values[$key] ?? '');
                    foreach ($nodes as $node) {
                        $text = $node->textContent;
                        $a = $offset;
                        $b = $a + strlen($text);
                        $offset = $b;
                        if ($b <= $start || $a >= $end) {
                            continue;
                        }
                        $node->textContent = substr($text, 0, max(0, $start - $a)).(! $inserted ? $replacement : '').($end < $b ? substr($text, $end - $a) : '');
                        $node->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                        $inserted = true;
                    }
                }
            }
            $zip->addFromString($name, $dom->saveXML());
        }
        $zip->close();
        $pdf = null;
        $conversionError = false;
        $profile = sys_get_temp_dir().'/contract-lo-'.Str::uuid();
        try {
            $process = new Process([config('contracts.soffice'), '-env:UserInstallation=file://'.$profile, '--headless', '--convert-to', 'pdf', '--outdir', dirname($path), $path]);
            $process->setTimeout(25);
            $process->mustRun();
            if (is_file(substr($path, 0, -5).'.pdf')) {
                $pdf = $directory.'/kontrak.pdf';
            } else {
                $conversionError = true;
            }
        } catch (\Throwable $e) {
            $conversionError = true;
            report($e);
        } finally {
            if (is_dir($profile)) {
                (new \Illuminate\Filesystem\Filesystem)->deleteDirectory($profile);
            }
        }

        return ['docx_path' => $docx, 'pdf_path' => $pdf, 'conversion_error' => $conversionError];
    }
}
