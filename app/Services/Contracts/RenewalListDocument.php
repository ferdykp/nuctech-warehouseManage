<?php

namespace App\Services\Contracts;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use ZipArchive;

class RenewalListDocument
{
    public function generate(Collection $rows, string $period): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pkwt-renewals-');
        if (! $path || ! copy(resource_path('contracts/renewal-list-v1.docx'), $path)) {
            throw new \RuntimeException('Dokumen ekspor gagal disiapkan.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            @unlink($path);
            throw new \RuntimeException('Template ekspor tidak dapat dibuka.');
        }
        try {
            $dom = new DOMDocument;
            $dom->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET);
            $xp = new DOMXPath($dom);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $table = $xp->query('//w:tbl')->item(0);
            $sample = $xp->query('./w:tr', $table)->item(1);
            foreach ($rows as $i => $row) {
                $copy = $sample->cloneNode(true);
                $e = $row['employee'];
                $values = [
                    (string) ($i + 1),
                    $e->position ?: '—',
                    // $e->name . ($row['legacy'] ? ' *' : ''),
                    $e->name . ($row['legacy']),
                    $e->bank_name ?: '—',
                    $e->bank_account_number ?: '—',
                    ContractRenewalList::amountLabel($row['amount'])
                ];
                foreach ($xp->query('./w:tc', $copy) as $n => $cell) {
                    $this->fill($xp, $cell, $values[$n]);
                }
                $table->insertBefore($copy, $sample);
            }
            $table->removeChild($sample);
            foreach ($xp->query('//w:t') as $text) {
                $text->textContent = str_replace(['${period}', '${total}'], [$period, $this->money($this->totalCents($rows))], $text->textContent);
            }
            $zip->addFromString('word/document.xml', $dom->saveXML());
        } catch (\Throwable $error) {
            $zip->close();
            @unlink($path);
            throw $error;
        }
        $zip->close();

        return $path;
    }

    public function totalCents(Collection $rows): int
    {
        return $rows->sum(fn($row) => (int) round((float) $row['amount'] * 100));
    }

    public function money(int $cents): string
    {
        return 'Rp' . number_format($cents / 100, $cents % 100 === 0 ? 0 : 2, ',', '.');
    }

    private function fill(DOMXPath $xp, \DOMNode $cell, string $value): void
    {
        $nodes = $xp->query('.//w:t', $cell);
        foreach ($nodes as $i => $node) {
            $node->textContent = $i === 0 ? $value : '';
            $node->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        }
    }
}
