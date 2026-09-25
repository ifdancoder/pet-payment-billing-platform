<?php

declare(strict_types=1);

$repositoryRoot = dirname(__DIR__);
$excludedDirectories = [
    '.agents',
    '.ai',
    '.claude',
    '.factory',
    '.git',
    '.grok',
    'node_modules',
    'vendor',
];
$excludedFiles = ['AGENTS.md', 'CLAUDE.md'];
$markdownFiles = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($repositoryRoot, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'md') {
        continue;
    }

    $relativePath = substr($file->getPathname(), strlen($repositoryRoot) + 1);
    $pathParts = explode(DIRECTORY_SEPARATOR, $relativePath);

    if (array_intersect($excludedDirectories, $pathParts) !== []) {
        continue;
    }

    if (in_array($file->getFilename(), $excludedFiles, true)) {
        continue;
    }

    $markdownFiles[] = $file->getPathname();
}

sort($markdownFiles);
$errors = [];

foreach ($markdownFiles as $path) {
    $contents = file_get_contents($path);

    if ($contents === false) {
        $errors[] = "Cannot read {$path}.";

        continue;
    }

    $isRussian = str_ends_with($path, '.ru.md');
    $peer = $isRussian
        ? substr($path, 0, -strlen('.ru.md')).'.md'
        : substr($path, 0, -strlen('.md')).'.ru.md';
    $head = substr($contents, 0, 1000);
    $peerName = basename($peer);

    if (! is_file($peer)) {
        $errors[] = "Missing language pair for {$path}.";
    }

    if ($isRussian) {
        if (! str_contains($head, 'English version') || ! str_contains($head, "]({$peerName})")) {
            $errors[] = "Missing English language switch in {$path}.";
        }
    } elseif ((! str_contains($head, 'Русская версия') && ! str_contains($head, 'Russian version'))
        || ! str_contains($head, "]({$peerName})")) {
        $errors[] = "Missing Russian language switch in {$path}.";
    }

    if (substr_count($contents, '```') % 2 !== 0) {
        $errors[] = "Unclosed fenced code block in {$path}.";
    }

    preg_match_all('/(?<!!)\[[^]]*]\(([^)]+)\)/', $contents, $matches);

    foreach ($matches[1] as $target) {
        $target = trim(explode(' ', trim($target))[0], '<>');

        if ($target === '' || preg_match('/^(?:#|https?:\/\/|mailto:)/', $target) === 1) {
            continue;
        }

        $localPath = explode('#', $target, 2)[0];

        if ($localPath !== '' && ! file_exists(dirname($path).DIRECTORY_SEPARATOR.$localPath)) {
            $errors[] = "Broken local link in {$path}: {$target}.";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

$pairCount = intdiv(count($markdownFiles), 2);
fwrite(STDOUT, 'Markdown OK: '.count($markdownFiles)." files, {$pairCount} EN/RU pairs.".PHP_EOL);
