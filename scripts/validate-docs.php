<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$requiredFiles = [
    'AGENTS.md',
    'LICENSE',
    'LICENSE-SCOPE.md',
    'docs/product/vision.md',
    'docs/product/mvp-scope.md',
    'docs/product/non-goals.md',
    'docs/architecture/system-context.md',
    'docs/architecture/module-map.md',
    'docs/architecture/data-flow.md',
    'docs/architecture/poe1-poe2-boundary.md',
    'docs/security/threat-model.md',
    'docs/compliance/ggg-integration-policy.md',
    'docs/compliance/source-register.md',
    'docs/compliance/funding-policy.md',
    'docs/progress.md',
];

for ($number = 1; $number <= 9; $number++) {
    $matches = glob(sprintf('%s/docs/adr/%04d-*.md', $root, $number));

    if ($matches === false || count($matches) !== 1) {
        $errors[] = sprintf('Missing or ambiguous ADR %04d.', $number);
    }
}

foreach ($requiredFiles as $relativePath) {
    if (! is_file($root.'/'.$relativePath)) {
        $errors[] = "Missing required file: {$relativePath}";
    }
}

$markdownFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || $file->getExtension() !== 'md') {
        continue;
    }

    $path = str_replace('\\', '/', $file->getPathname());

    if (preg_match('#/(vendor|node_modules|\.git)/#', $path) === 1) {
        continue;
    }

    $markdownFiles[] = $file->getPathname();
}

sort($markdownFiles);

foreach ($markdownFiles as $path) {
    $relative = ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
    $content = file_get_contents($path);

    if ($content === false) {
        $errors[] = "Unable to read {$relative}.";

        continue;
    }

    $lines = preg_split('/\R/', $content) ?: [];
    $h1Lines = array_filter($lines, static fn (string $line): bool => str_starts_with($line, '# '));

    if (($lines[0] ?? '') === '' || ! str_starts_with($lines[0], '# ')) {
        $errors[] = "{$relative} must begin with one H1 heading.";
    }

    if (count($h1Lines) !== 1) {
        $errors[] = sprintf('%s must contain exactly one H1 heading; found %d.', $relative, count($h1Lines));
    }

    foreach ($lines as $index => $line) {
        if (preg_match('/[ \t]+$/', $line) === 1) {
            $errors[] = sprintf('%s has trailing whitespace on line %d.', $relative, $index + 1);
        }
    }

    $fenceCount = count(array_filter(
        $lines,
        static fn (string $line): bool => str_starts_with($line, '```'),
    ));

    if ($fenceCount % 2 !== 0) {
        $errors[] = "{$relative} has an unbalanced fenced code block.";
    }

    preg_match_all('/(?<!!)\[[^]]*]\(([^)]+)\)/', $content, $links);

    foreach ($links[1] ?? [] as $target) {
        $target = trim($target, " <>\t\n\r\0\x0B");

        if ($target === '' || preg_match('#^(https?://|mailto:|\#)#', $target) === 1) {
            continue;
        }

        $pathPart = rawurldecode(explode('#', $target, 2)[0]);
        $resolved = dirname($path).'/'.$pathPart;

        if (! file_exists($resolved)) {
            $errors[] = "{$relative} has a broken local link: {$target}";
        }
    }
}

$notice = "This product isn't affiliated with or endorsed by Grinding Gear Games in any way.";

foreach (['AGENTS.md', 'README.md', 'docs/product/vision.md', 'docs/compliance/ggg-integration-policy.md'] as $relativePath) {
    $content = @file_get_contents($root.'/'.$relativePath);

    if (! is_string($content) || ! str_contains($content, $notice)) {
        $errors[] = "{$relativePath} is missing the exact GGG independence notice.";
    }
}

$progress = @file_get_contents($root.'/docs/progress.md');

if (is_string($progress)) {
    for ($number = 0; $number <= 15; $number++) {
        $label = sprintf('## Prompt %02d', $number);

        if (! str_contains($progress, $label)) {
            $errors[] = "docs/progress.md is missing {$label}.";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

printf(
    'Documentation validation passed: %d Markdown files, required files, local links, fences, headings, prompts, and notice.%s',
    count($markdownFiles),
    PHP_EOL,
);
