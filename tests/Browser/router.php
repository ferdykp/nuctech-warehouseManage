<?php
// Local QA only. Never expose this router through a production web server.
$root = dirname(__DIR__, 2);
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite'
    || !preg_match('~^/(?:private/)?tmp/nuctech-ux-[a-zA-Z0-9_-]+\.sqlite$~', (string) getenv('DB_DATABASE'))) {
    http_response_code(403); exit('Isolated testing database required.');
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/__responsive') {
    $width = (int) ($_GET['width'] ?? 375);
    if (!in_array($width, [320, 375, 768, 1024, 1440, 1920, 3840], true)) $width = 375;
    $target = (string) ($_GET['path'] ?? '/dashboard');
    if (!str_starts_with($target, '/') || str_starts_with($target, '//')) $target = '/dashboard';
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="id"><meta name="viewport" content="width=device-width"><title>Responsive QA</title><style>body{margin:0;background:#e2e8f0;font:14px system-ui}nav{padding:12px;display:flex;gap:12px;flex-wrap:wrap}iframe{display:block;border:0;background:white}</style><nav>';
    foreach ([320, 375, 768, 1024, 1440, 1920, 3840] as $option) {
        echo '<a href="?'.htmlspecialchars(http_build_query(['width' => $option, 'path' => $target]), ENT_QUOTES).'">'.$option.' px</a>';
    }
    echo '<output id="qa-metrics" style="display:block;padding:12px;max-width:100vw;white-space:pre-wrap">Menunggu pemeriksaan layout…</output><script>addEventListener("message", event => { if(event.origin === location.origin && event.data?.qaLayout) document.getElementById("qa-metrics").textContent = JSON.stringify(event.data.qaLayout); });</script></nav><iframe title="Application at '.$width.' pixels" width="'.$width.'" height="900" src="'.htmlspecialchars($target, ENT_QUOTES).'"></iframe></html>';
    return;
}
if (in_array($path, ['/storage/qa-receipt.png', '/storage/qa-receipt.pdf'], true)) {
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    header('Content-Type: '.($extension === 'pdf' ? 'application/pdf' : 'image/png'));
    readfile('/tmp/nuctech-ux-receipt.'.$extension); return;
}
$file = realpath($root.'/public'.$path);
if ($file && str_starts_with($file, $root.'/public/') && is_file($file)) return false;
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();
if (getenv('QA_BUILT_ASSETS') === '1') $app->make(Illuminate\Foundation\Vite::class)->useHotFile('/tmp/nuctech-ux-no-hot');
Illuminate\Support\Facades\Http::fake(['*' => Illuminate\Support\Facades\Http::response(['data' => [['date' => '2026-09-01', 'description' => 'Hari uji']]])]);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
if (str_contains($response->headers->get('Content-Type', ''), 'text/html') && $response->getStatusCode() === 200) {
    $probe = <<<'JS'
<script>
addEventListener('load', () => {
    const report = () => {
        const main = document.getElementById('main-content');
        const width = document.documentElement.clientWidth;
        const overflow = Array.from(document.querySelectorAll('#main-content *')).filter(element => {
            if (!element.getClientRects().length || element.closest('.overflow-x-auto, .fixed')) return false;
            return element.getBoundingClientRect().right > width + 2;
        }).slice(0, 6).map(element => ({tag:element.tagName, classes:element.className, text:element.textContent.trim().slice(0,50)}));
        const dialogs = Array.from(document.querySelectorAll('[data-ui-dialog]')).filter(element => element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden').map(element => { const r = element.getBoundingClientRect(); return {top:r.top,bottom:r.bottom,left:r.left,right:r.right,scrollHeight:element.scrollHeight,clientHeight:element.clientHeight}; });
        parent.postMessage({qaLayout:{title:document.title,width,rootScrollWidth:document.documentElement.scrollWidth,mainWidth:main?.clientWidth,mainScrollWidth:main?.scrollWidth,pdfPages:document.querySelectorAll('.pdf-page-wrapper canvas').length,overflow,dialogs}}, location.origin);
    };
    document.fonts.ready.then(report);
    new MutationObserver(() => requestAnimationFrame(report)).observe(document.body,{subtree:true,attributes:true,attributeFilter:['class','style'],childList:true});
});
</script>
JS;
    $response->setContent(str_replace('</body>', $probe.'</body>', $response->getContent()));
}
$response->send();
$kernel->terminate($request, $response);
