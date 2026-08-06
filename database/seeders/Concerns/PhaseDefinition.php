<?php

namespace Database\Seeders\Concerns;

interface PhaseDefinition
{
    public function name(): string;

    public function startDate(): ?string;

    public function endDate(): ?string;

    public function status(): string;

    public function remarks(): ?string;
}
