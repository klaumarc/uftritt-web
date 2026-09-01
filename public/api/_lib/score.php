<?php
// Scoring-Rubric: Findings -> 0–10 pro Kategorie + gewichtete Gesamtnote.
// Abzüge: bad zählt voll, warn 45 %, info/ok nichts – gewichtet nach Impact.

declare(strict_types=1);

function uc_category_order(): array
{
    return ['technik', 'seo', 'performance', 'inhalt', 'ux'];
}

function uc_category_labels(): array
{
    return [
        'technik' => 'Technik',
        'seo' => 'SEO',
        'performance' => 'Performance',
        'inhalt' => 'Inhalt und Conversion',
        'ux' => 'UX-Basis',
    ];
}

// Kategorie-Scores 0–10 (auf 0.1 gerundet, Boden/Decke 0/10)
function uc_score_categories(array $findingsByCat): array
{
    $statusW = ['bad' => 1.0, 'warn' => 0.45, 'info' => 0.0, 'ok' => 0.0];
    $impactW = ['high' => 3.2, 'medium' => 2.0, 'low' => 1.1];
    $scores = [];
    foreach (uc_category_order() as $cat) {
        $deduct = 0.0;
        foreach ($findingsByCat[$cat] ?? [] as $f) {
            $deduct += ($statusW[$f['status']] ?? 0.0) * ($impactW[$f['impact']] ?? 1.1);
        }
        $scores[$cat] = round(max(0.0, min(10.0, 10.0 - $deduct)), 1);
    }
    return $scores;
}

// Gesamt = gewichtetes Mittel; Noten A ≥8.5, B ≥7.0, C ≥5.5, D ≥4.0, F <4.0
function uc_score_overall(array $scores): array
{
    $w = ['seo' => 0.25, 'performance' => 0.25, 'technik' => 0.20, 'inhalt' => 0.20, 'ux' => 0.10];
    $sum = 0.0;
    foreach ($w as $k => $v) {
        $sum += ((float)($scores[$k] ?? 0.0)) * $v;
    }
    $score = round($sum, 1);
    $grade = $score >= 8.5 ? 'A' : ($score >= 7.0 ? 'B' : ($score >= 5.5 ? 'C' : ($score >= 4.0 ? 'D' : 'F')));
    return ['score' => $score, 'grade' => $grade];
}

// Wichtigstes Finding einer Kategorie: schlechtester Status, dann höchster Impact
function uc_top_finding(array $findings): ?array
{
    $statusR = ['bad' => 0, 'warn' => 1, 'info' => 2, 'ok' => 3];
    $impactR = ['high' => 0, 'medium' => 1, 'low' => 2];
    $best = null;
    $bestKey = [99, 99];
    foreach ($findings as $f) {
        $key = [$statusR[$f['status']] ?? 3, $impactR[$f['impact']] ?? 2];
        if ($key < $bestKey) {
            $bestKey = $key;
            $best = $f;
        }
    }
    if ($best === null) {
        return null;
    }
    return ['status' => $best['status'], 'title' => $best['title'], 'detail' => $best['detail']];
}

// Aktionsplan-Ranking: hohe Wirkung x kleiner Aufwand zuerst
function uc_action_rank(array $f): float
{
    $impact = ['high' => 3.0, 'medium' => 2.0, 'low' => 1.0][$f['impact']] ?? 1.0;
    $effortF = ['low' => 1.5, 'medium' => 1.0, 'high' => 0.6][$f['effort']] ?? 1.0;
    $statusF = ($f['status'] ?? '') === 'bad' ? 1.25 : 1.0;
    return $impact * $effortF * $statusF;
}
