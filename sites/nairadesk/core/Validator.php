<?php
declare(strict_types=1);

final class Validator
{
    private array $errors = [];
    private array $clean = [];

    public function __construct(private array $input) {}

    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = trim((string)($this->input[$field] ?? ''));
            if ($value === '') {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            } else {
                $this->clean[$field] = $value;
            }
        }
        return $this;
    }

    public function email(string $field, bool $required = true): self
    {
        $value = trim((string)($this->input[$field] ?? ''));
        if ($value === '' && !$required) {
            return $this;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Enter a valid email address.';
        } else {
            $this->clean[$field] = strtolower($value);
        }
        return $this;
    }

    public function min(string $field, int $length): self
    {
        $value = (string)($this->input[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$length} characters.";
        }
        return $this;
    }

    public function max(string $field, int $length): self
    {
        $value = (string)($this->input[$field] ?? '');
        if (mb_strlen($value) > $length) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be under {$length} characters.";
        }
        return $this;
    }

    public function matches(string $field, string $other): self
    {
        if ((string)($this->input[$field] ?? '') !== (string)($this->input[$other] ?? '')) {
            $this->errors[$field] = 'Passwords do not match.';
        }
        return $this;
    }

    public function pattern(string $field, string $regex, string $message): self
    {
        if (!preg_match($regex, (string)($this->input[$field] ?? ''))) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function integer(string $field, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX, bool $required = true): self
    {
        $raw = $this->input[$field] ?? '';
        if ($raw === '' && !$required) {
            return $this;
        }
        $value = filter_var($raw, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is invalid.';
        } else {
            $this->clean[$field] = $value;
        }
        return $this;
    }

    public function date(string $field, bool $future = false, bool $required = true): self
    {
        $value = trim((string)($this->input[$field] ?? ''));
        if ($value === '' && !$required) {
            return $this;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        if (!$dt || $dt->format('Y-m-d') !== $value) {
            $this->errors[$field] = 'Enter a valid date (YYYY-MM-DD).';
        } elseif ($future && $value < date('Y-m-d')) {
            $this->errors[$field] = 'Choose today or a future date.';
        } else {
            $this->clean[$field] = $value;
        }
        return $this;
    }

    public function in(string $field, array $allowed, bool $required = true): self
    {
        $value = (string)($this->input[$field] ?? '');
        if ($value === '' && !$required) {
            return $this;
        }
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is invalid.';
        } else {
            $this->clean[$field] = $value;
        }
        return $this;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function clean(): array
    {
        return $this->clean;
    }

    public function value(string $field, mixed $default = null): mixed
    {
        return $this->clean[$field] ?? $default;
    }
}
