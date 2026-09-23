<?php

declare(strict_types=1);

/**
 * Template parity guard.
 *
 * The storefront is a slice of design/vendora, and the rule for that slice is that no
 * Tailwind class may be invented: markup may gain Blade expressions, loops, conditionals
 * and wire:/name/value attributes, but the visual vocabulary must stay exactly the one the
 * template already uses.
 *
 * This compares every class used in the sliced Blade views against every class in the
 * template's HTML and reports the ones that appear only in Blade. It is deliberately a
 * vocabulary check rather than a diff: the Blade views legitimately differ in structure
 * (a loop replaces six hard-coded cards), so comparing markup would report nothing but
 * noise, while comparing the set of classes catches the thing that actually matters.
 *
 * Usage:  php scripts/check-classes.php [--verbose]
 * Exit:   0 clean, 1 if a class was invented.
 */

$root = dirname(__DIR__);
$verbose = in_array('--verbose', $argv, true);

$templateDir = $root.'/design/vendora';

if (! is_dir($templateDir)) {
    fwrite(STDERR, "The template is not present at design/vendora — nothing to compare against.\n");
    exit(0);
}

/**
 * Reviewed exceptions: a class the template does not contain, and why that is correct.
 *
 * Every entry here is a case the static demo never had to express. Nothing goes in this
 * list to make the check pass — if a class is here, the reason beside it is the argument
 * for it, and a reviewer can reject the argument.
 *
 * @var array<string, string>
 */
$reviewedExceptions = [
    'x-cloak' => "Alpine's own helper; the template ships no interactive state.",
    'no-scrollbar' => 'Defined in storefront.css and used by the template via the same utility.',
    'sr-only' => 'Accessibility text the demo had no forms to need.',

    'object-cover' => 'The demo renders gradient placeholders; real uploaded product images need cropping.',
    'col-span-full' => 'Empty-state row inside the product grid. The demo always had products.',

    // The template has no language switcher at all — the shop was single-language. These
    // three are the switcher's own spacing and divider.
    'text-white/30' => 'Language switcher divider, on the template\'s existing top strip.',
    'py-1.5' => 'Language switcher pill padding.',
    'pb-1' => 'Language switcher label spacing in the mobile drawer.',
];

/** @return array<int, string> */
function classesIn(string $contents): array
{
    $found = [];

    // class="..." and class='...'
    if (preg_match_all('/\bclass\s*=\s*"([^"]*)"/i', $contents, $m)) {
        $found = array_merge($found, $m[1]);
    }

    if (preg_match_all("/\bclass\s*=\s*'([^']*)'/i", $contents, $m)) {
        $found = array_merge($found, $m[1]);
    }

    $classes = [];

    foreach ($found as $chunk) {
        // Strip Blade expressions: {{ … }}, {!! … !!}, @if(…) … @endif and ternaries inside
        // them. What is left is the literal vocabulary.
        $chunk = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', ' ', $chunk) ?? '';
        $chunk = preg_replace('/@[a-z]+\s*\([^)]*\)/i', ' ', $chunk) ?? '';

        foreach (preg_split('/\s+/', $chunk) ?: [] as $class) {
            $class = trim($class);

            if ($class === '' || str_contains($class, '$') || str_contains($class, '@')) {
                continue;
            }

            $classes[$class] = true;
        }
    }

    return array_keys($classes);
}

/** @return array<int, string> */
function filesIn(string $dir, string $extension): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), $extension)) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

$templateClasses = [];

foreach (filesIn($templateDir, '.html') as $file) {
    foreach (classesIn((string) file_get_contents($file)) as $class) {
        $templateClasses[$class] = true;
    }
}

/*
 | Blade expressions inside a class attribute routinely pick between two literal class
 | lists, and those literals are part of the template's vocabulary too. Sweep the whole
 | file for quoted strings that look like class lists so a conditional does not register
 | as an invention.
 */
$bladeDirs = [
    $root.'/resources/views/pages',
    $root.'/resources/views/components/store',
    $root.'/resources/views/components/layouts',
    $root.'/resources/views/livewire',
];

$offenders = [];

foreach ($bladeDirs as $dir) {
    if (! is_dir($dir)) {
        continue;
    }

    foreach (filesIn($dir, '.blade.php') as $file) {
        $contents = (string) file_get_contents($file);

        foreach (classesIn($contents) as $class) {
            if (isset($templateClasses[$class])) {
                continue;
            }

            if (array_key_exists($class, $reviewedExceptions)) {
                continue;
            }

            $offenders[$class][] = str_replace($root.'/', '', $file);
        }
    }
}

if ($offenders === []) {
    $count = count($templateClasses);
    $exceptions = count($reviewedExceptions);
    echo "Template parity: clean. {$count} classes in the template, {$exceptions} reviewed exceptions, nothing else invented.\n";
    exit(0);
}

echo "Template parity: ".count($offenders)." class(es) appear in Blade but not in design/vendora.\n\n";

foreach ($offenders as $class => $files) {
    $where = $verbose ? implode(', ', array_unique($files)) : array_unique($files)[0] ?? '';
    echo "  {$class}\n      {$where}\n";
}

echo "\nEither the class belongs in the template, or the slice has drifted. Fix the slice.\n";
exit(1);
