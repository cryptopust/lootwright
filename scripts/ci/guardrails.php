<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$command = 'git -C '.escapeshellarg($root).' ls-files --cached --others --exclude-standard';
$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Guardrails could not enumerate repository files.\n");
    exit(2);
}

$paths = array_values(array_filter(array_map(
    static fn (string $path): string => str_replace('\\', '/', trim($path)),
    $output,
)));
sort($paths, SORT_STRING);

$failures = [];
$record = static function (string $path, string $reason) use (&$failures): void {
    $failures[] = "{$path}: {$reason}";
};
$contents = static function (string $path) use ($root): ?string {
    $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (! is_file($absolute) || filesize($absolute) > 2_000_000) {
        return null;
    }

    $value = file_get_contents($absolute);

    return is_string($value) && ! str_contains($value, "\0") ? $value : null;
};

foreach (['composer.lock', 'package-lock.json'] as $required) {
    if (! in_array($required, $paths, true)) {
        $record($required, 'required lock file is missing');
    }
}

foreach (['yarn.lock', 'pnpm-lock.yaml', 'bun.lock', 'bun.lockb'] as $forbidden) {
    if (in_array($forbidden, $paths, true)) {
        $record($forbidden, 'alternate package-manager lock file is prohibited');
    }
}

foreach ($paths as $path) {
    $lowerPath = strtolower($path);
    if (preg_match('#(^|/)\.env(?:\.|$)#D', $path) === 1
        && ! in_array($path, ['.env.example', 'deploy/env.production.example'], true)
    ) {
        $record($path, 'environment or secret file must not be version controlled');
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $protectedAsset = str_starts_with($path, 'public/') || str_starts_with($path, 'resources/');
    if ($protectedAsset && in_array($extension, [
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'ico', 'woff', 'woff2',
        'ttf', 'otf', 'mp3', 'ogg', 'wav', 'mp4', 'webm',
    ], true)) {
        $record($path, 'binary visual, font, audio, or video assets are prohibited in the production surface');
    }

    if ($protectedAsset && $extension === 'svg' && $path !== 'public/favicon.svg') {
        $record($path, 'only the reviewed Lootwright-original favicon SVG is allowed');
    }

    if (preg_match('#^(public|resources|database|rulesets|src/GameAdapters)/#', $path) === 1
        && in_array($extension, ['zip', '7z', 'gz', 'tar', 'sql', 'dump', 'sqlite', 'db', 'dat', 'bin', 'lua'], true)
    ) {
        $record($path, 'publisher dataset, archive, dump, or executable-like payload is prohibited');
    }

    if ($protectedAsset
        && preg_match('/(?:ggg|pathofexile|passive[-_ ]?tree|item[-_ ]?icon|trade[-_ ]?logo)/i', $lowerPath) === 1
    ) {
        $record($path, 'filename suggests prohibited GGG branding or protected game material');
    }

    $text = $contents($path);
    if ($text === null) {
        continue;
    }

    $privateKeyMarker = '-----BEGIN '.'PRIVATE KEY-----';
    $openAiKeyPattern = '/\b'.'sk-'.'[A-Za-z0-9_-]{20,}\b/';
    $githubTokenPattern = '/\b'.'gh'.'[pousr]_[A-Za-z0-9]{30,}\b/';
    $awsKeyPattern = '/\bA'.'KIA[0-9A-Z]{16}\b/';
    if (str_contains($text, $privateKeyMarker)
        || preg_match($openAiKeyPattern, $text) === 1
        || preg_match($githubTokenPattern, $text) === 1
        || preg_match($awsKeyPattern, $text) === 1
    ) {
        $record($path, 'content resembles a committed credential or private key');
    }

    $runtime = preg_match('#^(app|src|config|resources|routes)/#', $path) === 1;
    if ($runtime && str_contains($text, 'POE'.'SESSID')) {
        $record($path, 'GGG session credential handling is prohibited in runtime code');
    }

    if ($runtime && $path !== 'app/Modules/PolicyProvenance/PolicyDefaults.php'
        && preg_match('#/api/trade/(search|fetch|data)#i', $text) === 1
    ) {
        $record($path, 'undocumented Trade endpoint string is prohibited outside the deny registry and tests/docs');
    }

    if ($runtime && preg_match('/FUNDING_ENABLED\s*=\s*true|[\'\"]accepting_funds[\'\"]\s*=>\s*true|[\'\"]funding[\'\"]\s*=>\s*true/i', $text) === 1) {
        $record($path, 'runtime funding or payment acceptance cannot be enabled');
    }

    if (preg_match('/^[ \t]*(APP_KEY|OPENAI_API_KEY|POLICY_ADMIN_TOKEN|READINESS_TOKEN)[ \t]*=[ \t]*[^\r\n]+/m', $text) === 1
        && in_array($path, ['.env.example', 'deploy/env.production.example'], true)
    ) {
        $record($path, 'secret-bearing environment examples must leave secret values empty');
    }
}

$favicon = $contents('public/favicon.svg');
if ($favicon === null
    || ! str_contains($favicon, 'aria-label="Lootwright"')
    || str_contains(strtolower($favicon), 'path of exile')
) {
    $record('public/favicon.svg', 'favicon must remain the reviewed Lootwright-original vector');
}

$fundingConfig = $contents('config/funding.php') ?? '';
$securityConfig = $contents('config/security.php') ?? '';
$fundingPage = $contents('resources/js/pages/Funding.vue') ?? '';
if (! str_contains($fundingConfig, "env('FUNDING_ENABLED', false)")) {
    $record('config/funding.php', 'funding must default to false');
}
if (! str_contains($securityConfig, "'funding' => false")) {
    $record('config/security.php', 'funding execution must remain code-disabled');
}
if (preg_match('/<(?:a|form)\b|\bhref\s*=|https?:\/\//i', $fundingPage) === 1) {
    $record('resources/js/pages/Funding.vue', 'funding page must contain no payment, donation, sponsor, or outbound action');
}

$dependencyDocuments = strtolower(($contents('composer.json') ?? '').($contents('package.json') ?? ''));
foreach (['stripe', 'paypal', 'braintree', 'paddle', 'cashier', 'mollie'] as $paymentDependency) {
    if (str_contains($dependencyDocuments, $paymentDependency)) {
        $record('composer.json/package.json', "payment dependency '{$paymentDependency}' is prohibited while funding is disabled");
    }
}

$parseEnv = static function (string $path) use ($contents, $record): array {
    $text = $contents($path);
    if ($text === null) {
        $record($path, 'required environment reference is missing');

        return [];
    }
    $values = [];
    foreach (preg_split('/\R/', $text) ?: [] as $line) {
        if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/D', $line, $match) === 1) {
            $values[$match[1]] = trim($match[2]);
        }
    }

    return $values;
};

$localEnv = $parseEnv('.env.example');
foreach (['FUNDING_ENABLED', 'OPENAI_ENABLED', 'OUTBOUND_NETWORK_ENABLED'] as $name) {
    if (($localEnv[$name] ?? null) !== 'false') {
        $record('.env.example', "{$name} must default to false");
    }
}

$productionEnv = $parseEnv('deploy/env.production.example');
foreach ([
    'DEPLOYMENT_LOCKDOWN_MODE' => 'true',
    'POLICY_GLOBAL_KILL_SWITCH' => 'true',
    'IMPORTS_ENABLED' => 'false',
    'RULESETS_ENABLED' => 'false',
    'EXTERNAL_LINKS_ENABLED' => 'false',
    'FUNDING_ENABLED' => 'false',
    'OPENAI_ENABLED' => 'false',
    'OUTBOUND_NETWORK_ENABLED' => 'false',
    'HORIZON_DASHBOARD_ENABLED' => 'false',
] as $name => $expected) {
    if (($productionEnv[$name] ?? null) !== $expected) {
        $record('deploy/env.production.example', "{$name} must default to {$expected}");
    }
}

if ($failures !== []) {
    sort($failures, SORT_STRING);
    foreach ($failures as $failure) {
        fwrite(STDERR, "GUARDRAIL: {$failure}\n");
    }
    exit(1);
}

fwrite(STDOUT, 'Guardrails passed for '.count($paths)." repository files.\n");
