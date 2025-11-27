<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Shared\Infrastructure\Security\OpaqueTokenGenerator;

function generateSecret(int $length = 64): string
{
    $generator = new OpaqueTokenGenerator();
    return $generator->generate($length);
}

function replaceSecretsInEnvFiles(): void
{
    $envFiles = glob('.env*');
    foreach ($envFiles as $file) {
        if (str_contains($file, 'default')
            || !is_file($file)
        ) {
            continue;
        }
        replaceSecretsInEnvFile($file);
    }
}

function replaceSecretsInEnvFile(string $file): void
{
    $envFile = $file;

    if (!file_exists($envFile)) {
        echo "Error: .env file not found\n";
        return;
    }

    $content = file_get_contents($envFile);

    $secrets = [];
    $placeholderCount = substr_count($content, '%SECRET_HERE%');

    for ($i = 0; $i < $placeholderCount; $i++) {
        $secrets[] = generateSecret();
    }

    $secretIndex = 0;
    $updatedContent = preg_replace_callback(
        '/%SECRET_HERE%/',
        function() use ($secrets, &$secretIndex) {
            return $secrets[$secretIndex++];
        },
        $content
    );

    file_put_contents($envFile, $updatedContent);

    echo "\033[32m✓ Generated and replaced " . count($secrets) . " secrets in $file file\033[0m\n";
}

replaceSecretsInEnvFiles();
