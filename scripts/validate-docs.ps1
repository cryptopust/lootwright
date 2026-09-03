[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$errors = [System.Collections.Generic.List[string]]::new()

$requiredFiles = @(
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
    'docs/adr/0001-laravel-modular-monolith.md',
    'docs/adr/0002-deterministic-core.md',
    'docs/adr/0003-poe1-first-delivery.md',
    'docs/adr/0004-provider-neutral-optional-ai-port.md',
    'docs/adr/0005-immutable-versioned-rulesets.md',
    'docs/adr/0006-deny-by-default-integrations.md',
    'docs/adr/0007-manual-trade-filter-recipes.md',
    'docs/adr/0008-no-client-or-browser-integration.md',
    'docs/adr/0009-funding-off-by-default.md'
)

foreach ($relativePath in $requiredFiles) {
    $candidate = Join-Path $root $relativePath
    if (-not (Test-Path -LiteralPath $candidate -PathType Leaf)) {
        $errors.Add("Missing required file: $relativePath")
    }
}

$markdownFiles = Get-ChildItem -LiteralPath $root -Recurse -File -Filter '*.md' |
    Where-Object {
        $_.FullName -notmatch '[\\/](vendor|node_modules|storage|\.git)[\\/]'
    }

foreach ($file in $markdownFiles) {
    $relative = $file.FullName.Substring($root.Length).TrimStart('\', '/').Replace('\', '/')
    $content = Get-Content -LiteralPath $file.FullName -Raw
    $lines = $content -split "`r?`n"

    if ($lines.Count -eq 0 -or $lines[0] -notmatch '^# ') {
        $errors.Add("$relative must begin with one H1 heading")
    }

    $h1Count = ($lines | Where-Object { $_ -match '^# ' }).Count
    if ($h1Count -ne 1) {
        $errors.Add("$relative must contain exactly one H1 heading; found $h1Count")
    }

    for ($index = 0; $index -lt $lines.Count; $index++) {
        if ($lines[$index] -match '[ \t]+$') {
            $errors.Add("$relative has trailing whitespace on line $($index + 1)")
        }
    }

    $fenceCount = ($lines | Where-Object { $_ -match '^```' }).Count
    if (($fenceCount % 2) -ne 0) {
        $errors.Add("$relative has an unbalanced fenced code block")
    }

    $linkMatches = [regex]::Matches($content, '(?<!\!)\[[^\]]*\]\(([^)]+)\)')
    foreach ($match in $linkMatches) {
        $target = $match.Groups[1].Value.Trim()
        if ($target.StartsWith('<') -and $target.EndsWith('>')) {
            $target = $target.Substring(1, $target.Length - 2)
        }
        if ($target -match '^(https?://|mailto:|#)' -or [string]::IsNullOrWhiteSpace($target)) {
            continue
        }

        $pathPart = ($target -split '#', 2)[0]
        $pathPart = [System.Uri]::UnescapeDataString($pathPart)
        $resolved = Join-Path $file.DirectoryName $pathPart
        if (-not (Test-Path -LiteralPath $resolved)) {
            $errors.Add("$relative has a broken local link: $target")
        }
    }
}

$notice = "This product isn't affiliated with or endorsed by Grinding Gear Games in any way."
foreach ($relativePath in @('AGENTS.md', 'docs/product/vision.md', 'docs/compliance/ggg-integration-policy.md')) {
    $candidate = Join-Path $root $relativePath
    if ((Test-Path -LiteralPath $candidate) -and -not (Select-String -LiteralPath $candidate -SimpleMatch $notice -Quiet)) {
        $errors.Add("$relativePath is missing the exact GGG independence notice")
    }
}

$progressPath = Join-Path $root 'docs/progress.md'
if (Test-Path -LiteralPath $progressPath) {
    $progress = Get-Content -LiteralPath $progressPath -Raw
    foreach ($number in 0..15) {
        $label = '## Prompt {0:D2}' -f $number
        if (-not $progress.Contains($label)) {
            $errors.Add("docs/progress.md is missing $label")
        }
    }
}

if ($errors.Count -gt 0) {
    $errors | ForEach-Object { Write-Error $_ }
    exit 1
}

Write-Output "Documentation validation passed: $($markdownFiles.Count) Markdown files, required files, local links, fences, headings, prompts, and notice."
