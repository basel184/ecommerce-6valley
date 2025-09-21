<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ExportDocsToPdf extends Command
{
    protected $signature = 'docs:export-pdf {--source=docs : Source directory containing .md files} {--out=docs/pdf : Output directory for PDF files}';

    protected $description = 'Export all Markdown files in docs/ to PDF using mPDF (Arabic RTL friendly)';

    public function handle(): int
    {
        $sourceDir = base_path($this->option('source'));
        $outDir = base_path($this->option('out'));

        if (!File::isDirectory($sourceDir)) {
            $this->error("Source directory not found: {$sourceDir}");
            return 1;
        }
        File::ensureDirectoryExists($outDir);

        $mdFiles = collect(File::files($sourceDir))
            ->filter(fn($f) => Str::lower($f->getExtension()) === 'md')
            ->values();

        if ($mdFiles->isEmpty()) {
            $this->warn('No .md files found in ' . $sourceDir);
            return 0;
        }

        $this->info('Exporting ' . $mdFiles->count() . ' Markdown files to PDF...');
        $bar = $this->output->createProgressBar($mdFiles->count());
        $bar->start();

        foreach ($mdFiles as $file) {
            $markdown = File::get($file->getRealPath());
            $html = $this->convertMarkdownToHtml($markdown, $file->getFilename());

            $pdfPath = $outDir . DIRECTORY_SEPARATOR . Str::replaceLast('.md', '.pdf', $file->getFilename());

            // mPDF config with Arabic/RTL support
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 14,
                'margin_bottom' => 14,
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
                'tempDir' => storage_path('app/mpdf'),
                'fontDir' => $fontDirs,
                'fontdata' => $fontData + [
                    // يمكن إضافة خطوط عربية لاحقاً هنا (Tajawal, Cairo, Amiri) عند الحاجة
                ],
                'default_font' => 'dejavusans',
                'defaultfooterline' => 0,
                'defaultheaderline' => 0,
            ]);

            // اتجاه RTL عام، مع LTR في الأكواد
            $mpdf->SetDirectionality('rtl');
            $mpdf->WriteHTML($this->baseStyles(), 1);
            $mpdf->WriteHTML($html, 2);
            $mpdf->Output($pdfPath, 'F');

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('PDFs saved to: ' . $outDir);
        return 0;
    }

    private function convertMarkdownToHtml(string $md, string $title = 'Document'): string
    {
        // Code fences
        $md = preg_replace_callback('/```(\\w+)?\\n([\\s\\S]*?)```/m', function ($m) {
            $lang = $m[1] ?? '';
            $code = htmlspecialchars($m[2] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return "<pre class=\"codeblock ltr\"><code class=\"lang-{$lang}\">{$code}</code></pre>";
        }, $md);

        // Inline code
        $md = preg_replace('/`([^`]+)`/', '<code class="ltr">$1</code>', $md);

        $lines = preg_split('/\r?\n/', $md);
        $htmlLines = [];
        $inList = false;

        foreach ($lines as $line) {
            $trim = ltrim($line);
            if ($trim === '') {
                if ($inList) { $htmlLines[] = '</ul>'; $inList = false; }
                $htmlLines[] = '<br/>';
                continue;
            }

            if (Str::startsWith($trim, '### ')) {
                if ($inList) { $htmlLines[] = '</ul>'; $inList = false; }
                $htmlLines[] = '<h3>' . e(Str::after($trim, '### ')) . '</h3>';
                continue;
            }
            if (Str::startsWith($trim, '## ')) {
                if ($inList) { $htmlLines[] = '</ul>'; $inList = false; }
                $htmlLines[] = '<h2>' . e(Str::after($trim, '## ')) . '</h2>';
                continue;
            }
            if (Str::startsWith($trim, '# ')) {
                if ($inList) { $htmlLines[] = '</ul>'; $inList = false; }
                $htmlLines[] = '<h1>' . e(Str::after($trim, '# ')) . '</h1>';
                continue;
            }

            if (preg_match('/^-\s+(.+)/', $trim, $m) || preg_match('/^\*\s+(.+)/', $trim, $m)) {
                if (!$inList) { $htmlLines[] = '<ul>'; $inList = true; }
                $htmlLines[] = '<li>' . e($m[1]) . '</li>';
                continue;
            }

            if (Str::contains($line, '|') && Str::contains($line, '---')) {
                if ($inList) { $htmlLines[] = '</ul>'; $inList = false; }
                $htmlLines[] = '<pre class="tablepre ltr">' . e($line) . '</pre>';
                continue;
            }

            $htmlLines[] = '<p>' . e($line) . '</p>';
        }
        if ($inList) { $htmlLines[] = '</ul>'; }

        $header = '<div class="header ltr"><h1 class="rtl-title">' . e($title) . '</h1><div class="small">Generated from Markdown</div></div>';

        return $header . implode("\n", $htmlLines);
    }

    private function baseStyles(): string
    {
        return <<<CSS
        <style>
            body{ font-family: dejavusans, Arial, Helvetica, sans-serif; font-size:12pt; color:#222; direction: rtl; text-align: right; }
            .rtl-title{ direction: rtl; text-align: right; }
            .ltr{ direction:ltr; text-align:left; unicode-bidi:embed; }
            h1{ font-size:22pt; margin:12pt 0; }
            h2{ font-size:18pt; margin:10pt 0; }
            h3{ font-size:16pt; margin:8pt 0; }
            p{ margin:6pt 0; line-height:1.6; }
            ul{ margin:6pt 0 6pt 18pt; padding:0; }
            li{ margin:4pt 0; }
            code{ background:#f1f1f1; padding:1pt 3pt; border-radius:3pt; font-size:10pt; }
            pre.codeblock{ background:#f7f7f7; border:1px solid #ddd; padding:8pt; font-size:10pt; white-space:pre-wrap; word-wrap:anywhere; }
            .tablepre{ background:#fafafa; border:1px solid #eee; padding:6pt; }
            .header{border-bottom:1px solid #ddd; margin-bottom:10pt;}
            .small{color:#666; font-size:10pt;}
        </style>
        CSS;
    }
}