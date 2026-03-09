<?php

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string ...$fields): self
    {
        foreach ($fields as $f) {
            $v = $this->data[$f] ?? null;
            if ($v === null || $v === '') {
                $this->errors[$f] = ($this->errors[$f] ?? '') ?: 'This field is required.';
            }
        }
        return $this;
    }

    public function email(string $field): self
    {
        $v = $this->data[$field] ?? '';
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email address.';
        }
        return $this;
    }

    public function min(string $field, int $len): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && strlen($v) < $len) {
            $this->errors[$field] = "Must be at least {$len} characters.";
        }
        return $this;
    }

    public function max(string $field, int $len): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if (strlen($v) > $len) {
            $this->errors[$field] = "Must be at most {$len} characters.";
        }
        return $this;
    }

    public function unique(string $field, string $table, string $column, ?int $excludeId = null): self
    {
        $v = $this->data[$field] ?? '';
        if ($v === '') {
            return $this;
        }
        $pdo = DB::getInstance();
        $sql = "SELECT 1 FROM {$table} WHERE {$column} = ?";
        $params = [$v];
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $this->errors[$field] = 'This value is already in use.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }
}
