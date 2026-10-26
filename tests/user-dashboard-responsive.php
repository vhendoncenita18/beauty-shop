<?php
declare(strict_types=1);

$stylesheet = file_get_contents(dirname(__DIR__) . '/assets/css/styles.css');
if ($stylesheet === false) {
    fwrite(STDERR, "FAIL: shared stylesheet could not be read.\n");
    exit(1);
}

$hasNarrowHeaderWrap = preg_match(
    '/@media\s*\(max-width:\s*380px\)\s*\{.*?\.site-header\s*\{[^}]*height:\s*auto;[^}]*flex-wrap:\s*wrap;[^}]*\}.*?\.header-actions\s*\{[^}]*flex-wrap:\s*wrap;[^}]*\}/s',
    $stylesheet
) === 1;

if (!$hasNarrowHeaderWrap) {
    fwrite(STDERR, "FAIL: narrow User navigation must allow the header and actions to wrap.\n");
    exit(1);
}

echo "Narrow dashboard header wrapping rule is present.\n";
