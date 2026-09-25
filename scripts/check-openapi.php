<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

$repositoryRoot = dirname(__DIR__);
$autoloadPath = $repositoryRoot.'/services/billing-service/vendor/autoload.php';

if (! is_file($autoloadPath)) {
    fwrite(STDERR, 'Missing billing-service dependencies; run composer install there first.'.PHP_EOL);
    exit(1);
}

require $autoloadPath;

$document = Yaml::parseFile($repositoryRoot.'/docs/openapi/openapi.yaml');
$errors = [];
$httpMethods = ['get', 'put', 'post', 'delete', 'patch'];
$operationIds = [];
$specificationOperations = [];

if (($document['openapi'] ?? null) !== '3.1.0') {
    $errors[] = 'The OpenAPI version must be 3.1.0.';
}

foreach (($document['paths'] ?? []) as $path => $pathItem) {
    foreach ($pathItem as $method => $operation) {
        if (! in_array($method, $httpMethods, true)) {
            continue;
        }

        $operationId = $operation['operationId'] ?? null;

        if (! is_string($operationId) || $operationId === '') {
            $errors[] = "Missing operationId for {$method} {$path}.";
        } else {
            $operationIds[] = $operationId;
        }

        if (($operation['responses'] ?? []) === []) {
            $errors[] = "Missing responses for {$method} {$path}.";
        }

        $pathParameterNames = [];
        $parameters = array_merge($pathItem['parameters'] ?? [], $operation['parameters'] ?? []);

        foreach ($parameters as $parameter) {
            $resolved = resolveReference($parameter, $document, $errors);

            if (($resolved['in'] ?? null) !== 'path') {
                continue;
            }

            if (($resolved['required'] ?? false) !== true) {
                $errors[] = "Optional path parameter in {$method} {$path}.";
            }

            $pathParameterNames[] = $resolved['name'] ?? '';
        }

        preg_match_all('/{([^}]+)}/', $path, $matches);

        if (array_values(array_unique($pathParameterNames)) !== array_values($matches[1])) {
            $errors[] = "Path parameters do not match the template for {$method} {$path}.";
        }

        if ($path !== '/health') {
            $specificationOperations[strtoupper($method).' '.normalizePath($path)] = true;
        }
    }
}

if (count($operationIds) !== count(array_unique($operationIds))) {
    $errors[] = 'operationId values must be unique.';
}

walkReferences($document, $document, $errors);

$actualOperations = [];
$services = glob($repositoryRoot.'/services/*', GLOB_ONLYDIR) ?: [];

foreach ($services as $service) {
    if (! is_file($service.'/artisan')) {
        continue;
    }

    $process = new Process(['php', 'artisan', 'route:list', '--json', '--path=api/v1'], $service);
    $process->mustRun();
    $routes = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    foreach ($routes as $route) {
        $publicPath = '/'.preg_replace('#^api/#', '', $route['uri']);

        foreach (explode('|', $route['method']) as $method) {
            if ($method !== 'HEAD') {
                $actualOperations[$method.' '.normalizePath($publicPath)] = true;
            }
        }
    }
}

$missing = array_diff_key($actualOperations, $specificationOperations);
$extra = array_diff_key($specificationOperations, $actualOperations);

if ($missing !== []) {
    $errors[] = 'Routes missing from OpenAPI: '.implode(', ', array_keys($missing)).'.';
}

if ($extra !== []) {
    $errors[] = 'OpenAPI operations not implemented by Laravel: '.implode(', ', array_keys($extra)).'.';
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_unique($errors)).PHP_EOL);
    exit(1);
}

fwrite(
    STDOUT,
    'OpenAPI OK: '.count($document['paths']).' paths, '.count($operationIds)
        .' operations; Laravel parity '.count($actualOperations).'/'.count($specificationOperations).'.'.PHP_EOL,
);

/**
 * @param  array<string, mixed>  $value
 * @param  array<string, mixed>  $document
 * @param  array<int, string>  $errors
 * @return array<string, mixed>
 */
function resolveReference(array $value, array $document, array &$errors): array
{
    if (! isset($value['$ref'])) {
        return $value;
    }

    $reference = $value['$ref'];

    if (! is_string($reference) || ! str_starts_with($reference, '#/')) {
        $errors[] = 'Only local OpenAPI references are supported.';

        return [];
    }

    $resolved = $document;

    foreach (explode('/', substr($reference, 2)) as $segment) {
        $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);

        if (! is_array($resolved) || ! array_key_exists($segment, $resolved)) {
            $errors[] = "Unresolved OpenAPI reference: {$reference}.";

            return [];
        }

        $resolved = $resolved[$segment];
    }

    return is_array($resolved) ? $resolved : [];
}

/**
 * @param  array<string|int, mixed>  $value
 * @param  array<string, mixed>  $document
 * @param  array<int, string>  $errors
 */
function walkReferences(array $value, array $document, array &$errors): void
{
    foreach ($value as $key => $item) {
        if ($key === '$ref' && is_string($item)) {
            resolveReference(['$ref' => $item], $document, $errors);

            continue;
        }

        if (is_array($item)) {
            walkReferences($item, $document, $errors);
        }
    }
}

function normalizePath(string $path): string
{
    return preg_replace('/{[^}]+}/', '{}', $path) ?? $path;
}
