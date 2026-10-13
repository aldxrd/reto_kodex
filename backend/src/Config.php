<?php

declare(strict_types=1);

namespace Kodex;

use RuntimeException;

/**
 * Configuración leída de variables de entorno (y de un archivo .env opcional).
 * Las variables reales del entorno tienen prioridad sobre el .env.
 */
final class Config
{
    /** @var array<string, string> */
    private array $values;

    /** @param array<string, string> $values */
    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function load(?string $envFile = null): self
    {
        $values = [];
        if ($envFile !== null && is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $values[$key] = trim($value, "\"'");
            }
        }
        foreach (getenv() as $key => $value) {
            $values[$key] = (string) $value;
        }

        return new self($values);
    }

    /** @param array<string, string> $overrides */
    public function with(array $overrides): self
    {
        return new self(array_merge($this->values, $overrides));
    }

    public function get(string $key, ?string $default = null): string
    {
        $value = $this->values[$key] ?? $default;
        if ($value === null) {
            throw new RuntimeException("Falta la variable de configuración {$key}");
        }

        return $value;
    }

    public function int(string $key, int $default): int
    {
        return (int) ($this->values[$key] ?? $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        if (!isset($this->values[$key])) {
            return $default;
        }

        return in_array(strtolower($this->values[$key]), ['1', 'true', 'yes', 'on'], true);
    }
}
