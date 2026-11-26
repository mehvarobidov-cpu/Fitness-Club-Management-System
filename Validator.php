<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field] = "Майдони «{$label}» ҳатмист.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($value !== '' && $length > $max) {
            $this->errors[$field] = "Майдони «{$label}» набояд аз {$max} аломат зиёд бошад.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($value !== '' && $length < $min) {
            $this->errors[$field] = "Майдони «{$label}» бояд ҳадди ақал {$min} аломат дошта бошад.";
        }
        return $this;
    }

    public function date(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            return $this;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            $this->errors[$field] = "Санаи «{$label}» бояд дар формати YYYY-MM-DD бошад.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "Қимати майдони «{$label}» иҷозат дода нашудааст.";
        }
        return $this;
    }

    public function integer(string $field, string $label, int $min = 0): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = "Майдони «{$label}» бояд адади бутун бошад.";
        } elseif ($value !== null && $value !== '' && (int) $value < $min) {
            $this->errors[$field] = "Майдони «{$label}» набояд аз {$min} кам бошад.";
        }
        return $this;
    }

    public function numeric(string $field, string $label, float $min = 0): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "Майдони «{$label}» бояд рақам бошад.";
        } elseif ($value !== null && $value !== '' && (float) $value < $min) {
            $this->errors[$field] = "Майдони «{$label}» набояд аз {$min} кам бошад.";
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }
}
