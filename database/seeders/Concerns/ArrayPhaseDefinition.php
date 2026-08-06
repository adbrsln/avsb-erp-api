<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

/**
 * Phase definition backed by a raw array entry:
 * ['name' => string, 'start_date'? => 'Y-m-d', 'end_date'? => 'Y-m-d', 'status'? => string, 'remarks'? => string].
 * Constructor validates strictly — unknown keys, empty names, malformed dates,
 * end-before-start and out-of-list statuses all throw.
 */
class ArrayPhaseDefinition implements PhaseDefinition
{
    private const ALLOWED_KEYS = ['name', 'start_date', 'end_date', 'status', 'remarks'];

    private const ALLOWED_STATUSES = ['pending', 'in_progress', 'completed'];

    public function __construct(private array $data)
    {
        $this->validate();
    }

    public function name(): string
    {
        return $this->data['name'];
    }

    public function startDate(): ?string
    {
        return $this->data['start_date'] ?? null;
    }

    public function endDate(): ?string
    {
        return $this->data['end_date'] ?? null;
    }

    public function status(): string
    {
        return $this->data['status'] ?? 'pending';
    }

    public function remarks(): ?string
    {
        return $this->data['remarks'] ?? null;
    }

    /** @return array{name: string, start_date?: string, end_date?: string, status?: string, remarks?: string} */
    public function toArray(): array
    {
        return $this->data;
    }

    private function validate(): void
    {
        $unknown = array_diff(array_keys($this->data), self::ALLOWED_KEYS);
        if ($unknown !== []) {
            throw new RuntimeException('Invalid phase definition: unknown key(s) '.implode(', ', $unknown));
        }

        $name = $this->data['name'] ?? '';
        if (! is_string($name) || trim($name) === '') {
            throw new RuntimeException('Invalid phase definition: name is required');
        }

        $status = $this->data['status'] ?? 'pending';
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new RuntimeException('Invalid phase definition: status must be one of '.implode(', ', self::ALLOWED_STATUSES).", got \"{$status}\"");
        }

        foreach (['start_date', 'end_date'] as $key) {
            $value = $this->data[$key] ?? null;
            if ($value === null) {
                continue;
            }
            if (! is_string($value) || ! $this->validDate($value)) {
                throw new RuntimeException("Invalid phase definition: {$key} must be a valid Y-m-d date");
            }
        }

        if (isset($this->data['start_date'], $this->data['end_date'])
            && strtotime($this->data['end_date']) < strtotime($this->data['start_date'])) {
            throw new RuntimeException('Invalid phase definition: end_date cannot be before start_date');
        }
    }

    private function validDate(string $value): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }
}
