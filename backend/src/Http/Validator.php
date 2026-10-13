<?php

declare(strict_types=1);

namespace Kodex\Http;

/**
 * Validador sencillo y explícito. Acumula errores por campo y lanza un 422
 * con todos ellos al llamar a validate().
 *
 *   $v = new Validator($request->json());
 *   $email = $v->email('email');
 *   $password = $v->string('password', min: 8);
 *   $v->validate();
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    public function string(string $field, int $min = 1, int $max = 255, bool $required = true): ?string
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            if ($required) {
                $this->errors[$field] = 'Este campo es obligatorio.';
            }

            return null;
        }
        if (!is_string($value)) {
            $this->errors[$field] = 'Debe ser texto.';

            return null;
        }
        $value = trim($value);
        $length = mb_strlen($value);
        if ($length < $min) {
            $this->errors[$field] = "Debe tener al menos {$min} caracteres.";
        } elseif ($length > $max) {
            $this->errors[$field] = "Debe tener como máximo {$max} caracteres.";
        }

        return $value;
    }

    public function email(string $field, bool $required = true): ?string
    {
        $value = $this->string($field, 3, 160, $required);
        if ($value !== null && !isset($this->errors[$field]) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'Debe ser un correo electrónico válido.';
        }

        return $value !== null ? mb_strtolower($value) : null;
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enum
     * @return T|null
     */
    public function enum(string $field, string $enum, bool $required = true): ?\BackedEnum
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$field] = 'Este campo es obligatorio.';
            }

            return null;
        }
        $case = is_string($value) ? $enum::tryFrom($value) : null;
        if ($case === null) {
            $options = implode(', ', array_map(static fn ($c) => $c->value, $enum::cases()));
            $this->errors[$field] = "Valor no válido. Opciones: {$options}.";
        }

        return $case;
    }

    public function int(string $field, bool $required = true, int $min = 1): ?int
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$field] = 'Este campo es obligatorio.';
            }

            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < $min) {
            $this->errors[$field] = "Debe ser un entero mayor o igual a {$min}.";

            return null;
        }

        return (int) $value;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /** @throws HttpException 422 */
    public function validate(): void
    {
        if ($this->errors !== []) {
            throw HttpException::validation($this->errors);
        }
    }
}
