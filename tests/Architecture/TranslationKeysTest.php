<?php

/*
| Chuỗi dịch bằng khoá có namespace (ADR-049, docs/20-roadmap/i18n-keyed-translations.md §3).
|
| Luật 1, 4, 5 có mốc ngoại lệ `translation-baseline.php` (theo file) trong lúc chuyển đổi — mốc chỉ được GIẢM:
|   - file không có trong mốc, hoặc số vi phạm vượt mốc → đỏ;
|   - file đã sạch / ít vi phạm hơn mốc → đỏ tới khi hạ mốc: chạy lại với VANI_UPDATE_TRANSLATION_BASELINE=1
|     (chỉ hạ số, không bao giờ thêm file mới).
*/

require_once __DIR__.'/translations.php';

const TRANSLATION_BASELINE = __DIR__.'/translation-baseline.php';

/**
 * @return array{raw_calls: array<string, int>, hardcoded: array<string, int>, json: list<string>}
 */
function translationBaseline(): array
{
    return is_file(TRANSLATION_BASELINE) ? require TRANSLATION_BASELINE : ['raw_calls' => [], 'hardcoded' => [], 'json' => []];
}

/**
 * Hạ mốc (không thêm file mới) khi chạy với VANI_UPDATE_TRANSLATION_BASELINE=1.
 *
 * @param  array<string, int>|list<string>  $current
 */
function translationLowerBaseline(string $rule, array $current): void
{
    if (getenv('VANI_UPDATE_TRANSLATION_BASELINE') !== '1') {
        return;
    }
    $baseline = translationBaseline();
    $baseline[$rule] = $rule === 'json'
        ? array_values(array_intersect($baseline['json'], $current))
        : array_filter(array_map(fn (string $file): int => min($baseline[$rule][$file], $current[$file] ?? 0), array_combine(array_keys($baseline[$rule]), array_keys($baseline[$rule]))));
    ksort($baseline['raw_calls']);
    ksort($baseline['hardcoded']);
    file_put_contents(TRANSLATION_BASELINE, "<?php\n\n// Mốc ngoại lệ của TranslationKeysTest (ADR-049) — chỉ được giảm. Hạ mốc: VANI_UPDATE_TRANSLATION_BASELINE=1.\n\nreturn ".var_export($baseline, true).";\n");
}

/**
 * @param  array<string, int>  $current
 * @param  array<string, int>  $baseline
 * @return array{over: list<string>, stale: list<string>}
 */
function translationCompare(array $current, array $baseline): array
{
    $over = [];
    foreach ($current as $file => $count) {
        if ($count > ($baseline[$file] ?? 0)) {
            $over[] = "{$file}: {$count} (mốc ".($baseline[$file] ?? 0).')';
        }
    }
    $stale = [];
    foreach ($baseline as $file => $count) {
        if (($current[$file] ?? 0) < $count) {
            $stale[] = "{$file}: còn ".($current[$file] ?? 0)." (mốc {$count})";
        }
    }

    return ['over' => $over, 'stale' => $stale];
}

it('luật 1: hàm dịch nhận khoá namespace::nhóm.khoá, không nhận câu', function () {
    $current = translationRawCallOffenders();
    translationLowerBaseline('raw_calls', $current);
    $result = translationCompare($current, translationBaseline()['raw_calls']);

    expect($result['over'])->toBe([], 'Dùng khoá `namespace::nhóm.khoá` (ADR-049), không dùng câu làm khoá')
        ->and($result['stale'])->toBe([], 'Đã bớt vi phạm — hạ mốc: VANI_UPDATE_TRANSLATION_BASELINE=1');
});

it('luật 2: mọi khoá tĩnh có bản vi và en', function () {
    expect(translationMissingKeys())->toBe([]);
});

it('luật 3: vi và en cùng tập khoá, cùng tham số :name', function () {
    expect(translationParityProblems())->toBe([]);
});

it('luật 4: không dùng bản dịch JSON (lang/*.json, loadJsonTranslationsFrom, addJsonPath)', function () {
    $current = translationJsonUsages();
    translationLowerBaseline('json', $current);
    $baseline = translationBaseline()['json'];

    expect(array_values(array_diff($current, $baseline)))->toBe([], 'Bản dịch JSON bị bỏ (ADR-049)')
        ->and(array_values(array_diff($baseline, $current)))->toBe([], 'Đã gỡ — hạ mốc: VANI_UPDATE_TRANSLATION_BASELINE=1');
});

it('luật 5: không còn chữ tiếng Việt ngoài hàm dịch (trừ CLI, log, health-check, exception cho dev)', function () {
    $current = translationHardcodedOffenders();
    translationLowerBaseline('hardcoded', $current);
    $result = translationCompare($current, translationBaseline()['hardcoded']);

    expect($result['over'])->toBe([], 'Chuyển chữ hiển thị sang khoá dịch (ADR-049)')
        ->and($result['stale'])->toBe([], 'Đã bớt vi phạm — hạ mốc: VANI_UPDATE_TRANSLATION_BASELINE=1');
});
