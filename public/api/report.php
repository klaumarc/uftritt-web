<?php
// GET /api/report.php?id=… – Rendert einen gespeicherten Report als eigenständige
// HTML-Seite (inline-CSS, Uftritt-Farben, noindex). ALLE Ausgaben escaped.

declare(strict_types=1);

require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/store.php';
require __DIR__ . '/_lib/score.php';

uc_cleanup_maybe();

$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
$report = uc_report_load($id);

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Status- und Label-Definitionen für die Darstellung
$UC_STATUS = [
    'ok' => ['✓', 'In Ordnung', '#1a7f37'],
    'warn' => ['!', 'Verbesserungspotenzial', '#b26a00'],
    'bad' => ['✕', 'Kritisch', '#c4281c'],
    'info' => ['i', 'Hinweis', '#014080'],
];
$UC_IMPACT = ['high' => 'hoch', 'medium' => 'mittel', 'low' => 'tief'];
$UC_EFFORT = ['low' => 'klein', 'medium' => 'mittel', 'high' => 'gross'];

// Gemeinsamer Seitenkopf/-fuss (auch für die 404-Seite)
function uc_page_head(string $title): void
{
    ?>
<!doctype html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?></title>
<style>
@font-face{font-family:"Rational";src:url("/fonts/rationaltext-book-webfont.woff2") format("woff2");font-weight:400;font-display:swap}
@font-face{font-family:"Rational";src:url("/fonts/rationaltext-medium-webfont.woff2") format("woff2");font-weight:500;font-display:swap}
@font-face{font-family:"Rational";src:url("/fonts/rationaltext-bold-webfont.woff2") format("woff2");font-weight:700;font-display:swap}
@font-face{font-family:"Rational";src:url("/fonts/rationaltext-extrabold-webfont.woff2") format("woff2");font-weight:800;font-display:swap}
*{box-sizing:border-box}
body{margin:0;font-family:"Rational",system-ui,-apple-system,sans-serif;color:#014080;background:#f5f7fa;line-height:1.45;font-weight:500}
h1,h2,h3{line-height:1.15;margin:0}
a{color:#014080}
.wrap{max-width:920px;margin:0 auto;padding:0 clamp(1rem,3vw,2rem)}
.band{background:#ffd23c;padding:.7rem 0;font-weight:700;font-size:.95rem}
.band a{text-decoration:none}
.badge{flex:0 0 auto;width:96px;height:96px;background:#014080;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center}
.badge .g{font-size:2.6rem;font-weight:800;line-height:1}
.badge .s{font-size:.85rem;opacity:.85;margin-top:.15rem}
.head-card{background:#fff;margin-top:1.75rem;padding:clamp(1.25rem,3vw,2rem);display:flex;gap:1.5rem;align-items:center;flex-wrap:wrap}
.head-card .meta{font-size:.9rem;color:rgba(1,64,128,.55);margin-top:.4rem}
.scorebars{margin-top:1rem;width:100%;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.6rem 1.25rem}
.scorebar-row{font-size:.85rem}
.scorebar-row .sb-head{display:flex;justify-content:space-between;align-items:baseline;gap:.5rem}
.scorebar-row .val{font-weight:700;white-space:nowrap}
.scorebar{height:7px;background:#e6ebf2;margin-top:.2rem}
.scorebar>span{display:block;height:100%;background:#ffd23c}
.cat{background:#fff;margin-top:1.5rem;padding:clamp(1.25rem,3vw,2rem)}
.cat-head{display:flex;justify-content:space-between;align-items:baseline;gap:1rem;border-bottom:2px solid #f5f7fa;padding-bottom:.6rem;margin-bottom:.25rem}
.cat-head .score{font-weight:800;white-space:nowrap}
.llm-note{color:rgba(1,64,128,.75);margin:.9rem 0 .4rem}
.finding{display:flex;gap:.75rem;padding:.7rem 0;border-bottom:1px solid #eef2f7}
.finding:last-child{border-bottom:0}
.st{flex:0 0 22px;width:22px;height:22px;color:#fff;font-weight:800;font-size:.8rem;display:flex;align-items:center;justify-content:center;margin-top:2px;border-radius:50%}
.f-title{font-weight:700}
.f-detail{font-size:.95rem;margin-top:.15rem}
.chips{font-size:.78rem;color:rgba(1,64,128,.55);margin-top:.3rem}
.chip{display:inline-block;background:#f5f7fa;padding:.05rem .5rem;margin-right:.35rem}
.plan{background:#fff;margin-top:1.5rem;padding:clamp(1.25rem,3vw,2rem)}
.plan ol{padding-left:1.4rem}
.plan li{margin:.55rem 0}
.plan .tag{display:inline-block;background:#ffd23c;font-size:.75rem;font-weight:700;padding:.05rem .45rem;margin-right:.4rem}
.plan .more li{margin:.3rem 0;font-size:.95rem}
.cta{background:#da2c67;color:#fff;padding:clamp(1.5rem,4vw,2.5rem);margin-top:2rem;text-align:center}
.cta h2{color:#fff;margin:0 0 .5rem}
.cta p{margin:0 0 1.1rem}
.q-score{display:inline-block;background:#014080;color:#fff;font-weight:800;padding:.1rem .6rem;margin-right:.5rem}
.quotebox blockquote{margin:.9rem 0 0;padding:.7rem 1rem;background:#f5f7fa}
.quotebox .q-src{display:block;font-size:.78rem;font-style:normal;color:rgba(1,64,128,.55);margin-top:.3rem}
.palette{display:flex;gap:.9rem;flex-wrap:wrap;margin:.9rem 0 .3rem}
.swatch-wrap{text-align:center}
.swatch{width:56px;height:56px}
.swatch-hex{display:block;font-size:.7rem;color:rgba(1,64,128,.55);margin-top:.25rem}
.labnote{font-size:.85rem;color:rgba(1,64,128,.55);margin:.2rem 0 .8rem}
.labrow{display:flex;justify-content:space-between;gap:1rem;padding:.35rem 0;border-bottom:1px solid #eef2f7;font-size:.95rem}
.labrow:last-child{border-bottom:0}
.lab-pass{color:#1a7f37;font-weight:700}
.lab-fail{color:#c4281c;font-weight:700}
.btn{display:inline-block;background:#fff;color:#014080;font-weight:700;padding:.85rem 1.7rem;text-decoration:none;border:0;cursor:pointer;font-size:1rem;font-family:inherit}
.btn:hover{background:#ffd23c}
.printrow{text-align:center;margin-top:1.25rem}
.btn-print{background:transparent;color:#014080;border:2px solid #014080}
.btn-print:hover{background:#014080;color:#fff}
footer{font-size:.85rem;color:rgba(1,64,128,.55);text-align:center;padding:2rem 1rem}
@page{margin:15mm}
@media print{
  body{background:#fff;font-size:11pt}
  .band,.cta,.printrow{display:none !important}
  .wrap{max-width:none;padding:0}
  .cat,.plan,.head-card{page-break-inside:avoid;border:1px solid #d9e0ea;margin-top:1rem}
  a{color:#014080;text-decoration:none}
}
</style>
</head>
<body>
<div class="band"><div class="wrap"><a href="https://www.uftritt.ch/">uftritt.ch</a> · Website-Check</div></div>
    <?php
}

function uc_page_foot(): void
{
    ?>
<footer>
  Analyse teilweise KI-erstellt, redaktionell geprüfte Methodik · Report wird nach 90 Tagen gelöscht · uftritt.ch · <a href="https://www.uftritt.ch/impressum/" style="color:inherit">Impressum</a> · <a href="https://www.uftritt.ch/datenschutz/" style="color:inherit">Datenschutz</a>
</footer>
</body>
</html>
    <?php
}

// ---- Unbekannte ID -> freundliche 404-Seite im gleichen Stil ----
if ($report === null) {
    http_response_code(404);
    uc_page_head('Report nicht gefunden · Website-Check uftritt.ch');
    ?>
<div class="wrap">
  <div class="head-card">
    <div>
      <h1 style="font-size:clamp(1.6rem,4vw,2.2rem)">Dieser Report ist nicht (mehr) verfügbar</h1>
      <p class="meta">Möglicherweise ist der Link unvollständig, oder der Report ist älter als 90 Tage und wurde gelöscht.</p>
    </div>
  </div>
  <div class="cta">
    <h2>Einfach neu prüfen</h2>
    <p>Der Website-Check dauert nur eine Minute, danach bekommst du einen frischen Report.</p>
    <a class="btn" href="/website-check/">Neuen Check starten</a>
  </div>
</div>
    <?php
    uc_page_foot();
    exit;
}

// ---- Report rendern ----
$labels = uc_category_labels();
$order = uc_category_order();
$domain = (string)$report['domain'];
$created = date('d.m.Y', strtotime((string)$report['created']));
$overall = $report['overall'];
$llm = (array)($report['llm'] ?? []);
$llmFull = (array)($llm['full'] ?? []);

// Aktionsplan: warn/bad-Findings nach Wirkung x Aufwand sortiert
$actionable = [];
foreach ($order as $cat) {
    foreach ((array)($report['findings'][$cat] ?? []) as $f) {
        if (in_array($f['status'], ['warn', 'bad'], true)) {
            $f['cat_label'] = $labels[$cat];
            $actionable[] = $f;
        }
    }
}
usort($actionable, static function (array $a, array $b): int {
    return uc_action_rank($b) <=> uc_action_rank($a);
});
$top3 = array_slice($actionable, 0, 3);
$rest = array_slice($actionable, 3);

uc_page_head('Website-Check: ' . $domain . ' · uftritt.ch');
?>
<div class="wrap">

  <div class="head-card">
    <div class="badge"><span class="g"><?= e($overall['grade']) ?></span><span class="s"><?= e(number_format((float)$overall['score'], 1, ',', '')) ?>/10</span></div>
    <div style="flex:1 1 260px">
      <h1 style="font-size:clamp(1.6rem,4vw,2.3rem)"><?= e($domain) ?></h1>
      <p class="meta">Analysiert am <?= e($created) ?> · <?= (int)($report['pages_crawled'] ?? 1) ?> Seite(n) gecrawlt · <?= e((string)$report['url']) ?><?php
        $brand = (array)($report['brand'] ?? []);
        $brandBits = array_filter([trim((string)($brand['name'] ?? '')), trim((string)($brand['place'] ?? ''))]);
        if ($brandBits !== []) { echo ' · ' . e(implode(', ', $brandBits)); }
        if (!empty($report['industry'])) { echo ' · Branche: ' . e((string)$report['industry']); }
      ?></p>
      <div class="scorebars">
        <?php foreach ($order as $cat): $sv = (float)$report['scores'][$cat]; ?>
        <div class="scorebar-row"><div class="sb-head"><span><?= e($labels[$cat]) ?></span><span class="val"><?= e(number_format($sv, 1, ',', '')) ?></span></div>
          <div class="scorebar"><span style="width:<?= (int)round($sv * 10) ?>%"></span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if (trim((string)($llmFull['intro'] ?? '')) !== ''): ?>
  <div class="cat"><p style="margin:0"><?= nl2br(e($llmFull['intro'])) ?></p></div>
  <?php endif; ?>

  <?php $lp = $llm['leseprobe'] ?? null; ?>
  <?php if (is_array($lp) && trim((string)($lp['kommentar'] ?? '')) !== ''): ?>
  <div class="cat quotebox">
    <h2 style="font-size:clamp(1.2rem,2.5vw,1.5rem)">Leseprobe: So wirken die Texte</h2>
    <p style="margin:.6rem 0 .2rem"><span class="q-score"><?= (int)$lp['score'] ?>/5</span> Textwirkung beim ersten Lesen</p>
    <p style="margin:.4rem 0 0"><?= nl2br(e((string)$lp['kommentar'])) ?></p>
    <?php if (trim((string)($lp['zitat'] ?? '')) !== ''): ?>
    <blockquote>«<?= e((string)$lp['zitat']) ?>»<span class="q-src">Wörtlich aus der Website übernommen, als Beleg für die Einschätzung</span></blockquote>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php
    $pal = (array)($report['palette'] ?? []);
    $palColors = (array)($pal['colors'] ?? []);
    $farb = trim((string)($llm['farbeindruck'] ?? ''));
  ?>
  <?php if ($palColors !== []): ?>
  <div class="cat">
    <h2 style="font-size:clamp(1.2rem,2.5vw,1.5rem)">Farbpalette der Website</h2>
    <p class="labnote">Aus dem eingebetteten Design-Code der Startseite gelesen<?= !empty($pal['gradients']) ? ', inklusive Verläufen' : '' ?>.</p>
    <div class="palette">
      <?php foreach ($palColors as $c): if (!is_array($c) || empty($c['hex'])) { continue; } ?>
      <div class="swatch-wrap"><div class="swatch" style="background:<?= e((string)$c['hex']) ?>"></div><span class="swatch-hex"><?= e((string)$c['hex']) ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php if ($farb !== ''): ?>
    <p class="llm-note"><?= nl2br(e($farb)) ?> (Eindruck unserer KI zur Farbwahl, fliesst nicht in die Note ein.)</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php foreach ($order as $cat): ?>
  <div class="cat" id="<?= e($cat) ?>">
    <div class="cat-head">
      <h2 style="font-size:clamp(1.2rem,2.5vw,1.5rem)"><?= e($labels[$cat]) ?></h2>
      <span class="score"><?= e(number_format((float)$report['scores'][$cat], 1, ',', '')) ?>/10</span>
    </div>
    <?php $catNote = trim((string)($llmFull['per_category'][$cat] ?? '')); ?>
    <?php if ($catNote !== ''): ?>
    <p class="llm-note"><?= nl2br(e($catNote)) ?></p>
    <?php endif; ?>
    <?php foreach ((array)($report['findings'][$cat] ?? []) as $f):
        $st = $UC_STATUS[$f['status']] ?? $UC_STATUS['info']; ?>
    <div class="finding">
      <span class="st" style="background:<?= e($st[2]) ?>" title="<?= e($st[1]) ?>"><?= e($st[0]) ?></span>
      <div>
        <div class="f-title"><?= e($f['title']) ?></div>
        <div class="f-detail"><?= e($f['detail']) ?></div>
        <div class="chips">
          <span class="chip">Wirkung: <?= e($UC_IMPACT[$f['impact']] ?? $f['impact']) ?></span>
          <span class="chip">Aufwand: <?= e($UC_EFFORT[$f['effort']] ?? $f['effort']) ?></span>
          <span class="chip"><?= e($st[1]) ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <?php $psi = (array)($report['psi'] ?? []); ?>
  <?php if (isset($psi['score']) || !empty($psi['a11y'])): ?>
  <div class="cat">
    <h2 style="font-size:clamp(1.2rem,2.5vw,1.5rem)">Labormessung via Google Lighthouse</h2>
    <p class="labnote">Zusatzmessung durch die PageSpeed-Technik von Google (mobil, simuliertes Mittelklasse-Handy). Sie ergänzt unsere eigene Analyse und fliesst nicht in die Note ein.</p>
    <?php if (isset($psi['score'])): ?>
    <div class="labrow"><span>Performance-Wert (Lighthouse)</span><span><strong><?= (int)$psi['score'] ?>/100</strong></span></div>
    <div class="labrow"><span>Grösster Inhalt sichtbar nach</span><span><?= e((string)($psi['lcp'] ?? '')) ?></span></div>
    <div class="labrow"><span>Layout-Sprünge (CLS)</span><span><?= e((string)($psi['cls'] ?? '')) ?></span></div>
    <?php endif; ?>
    <?php foreach ((array)($psi['a11y'] ?? []) as $a): if (!is_array($a)) { continue; } ?>
    <div class="labrow"><span><?= e((string)($a['label'] ?? '')) ?></span><span class="<?= !empty($a['passed']) ? 'lab-pass' : 'lab-fail' ?>"><?= !empty($a['passed']) ? 'Bestanden' : 'Nicht bestanden' ?></span></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($actionable !== []): ?>
  <div class="plan">
    <h2 style="font-size:clamp(1.2rem,2.5vw,1.5rem)">Aktionsplan</h2>
    <p style="color:rgba(1,64,128,.55);margin:.4rem 0 1rem">Sortiert nach Wirkung und Aufwand: oben anfangen lohnt sich am meisten.</p>
    <ol>
      <?php foreach ($top3 as $f): ?>
      <li><span class="tag"><?= e($f['cat_label']) ?></span><strong><?= e($f['title']) ?></strong>: <?= e($f['detail']) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php if ($rest !== []): ?>
    <h3 style="font-size:1rem;margin-top:1.25rem">Weitere Massnahmen</h3>
    <ul class="more">
      <?php foreach ($rest as $f): ?>
      <li><span class="tag"><?= e($f['cat_label']) ?></span><?= e($f['title']) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if (trim((string)($llmFull['closing'] ?? '')) !== ''): ?>
  <div class="cat"><p style="margin:0"><?= nl2br(e($llmFull['closing'])) ?></p></div>
  <?php endif; ?>

  <div class="cta">
    <h2>Gratis 20-Minuten-Besprechung</h2>
    <p>Wir gehen den Report mit dir durch und zeigen, welche Massnahmen sich zuerst lohnen, unverbindlich und ohne Verkaufsdruck.</p>
    <a class="btn" href="/besprechung/?domain=<?= e(rawurlencode($domain)) ?>">Besprechung anfragen</a>
  </div>

  <div class="printrow">
    <button class="btn btn-print" type="button" onclick="window.print()">Als PDF speichern</button>
  </div>

</div>
<?php
uc_page_foot();
