<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Phase;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    protected ?Model $subject = null;

    protected array $properties = [];

    protected string $logName = 'default';

    public static function on(Model $subject): self
    {
        $instance = new self;
        $instance->subject = $subject;

        return $instance;
    }

    public function withProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function inLog(string $logName): self
    {
        $this->logName = $logName;

        return $this;
    }

    private function detectProjectId(?Model $model): ?int
    {
        if (! $model) {
            return null;
        }
        if (isset($model->project_id)) {
            return (int) $model->project_id;
        }
        if ($model instanceof Project) {
            return (int) $model->id;
        }
        if ($model instanceof Phase) {
            return (int) $model->project_id;
        }
        try {
            if (method_exists($model, 'phase') && ($phase = $model->phase)) {
                return (int) $phase->project_id;
            }
            if (method_exists($model, 'project') && ($project = $model->project)) {
                return (int) $project->id;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    public function log(string $description, ?string $event = null): ?ActivityLog
    {
        try {
            $causerId = ActivityLogContext::getCauserId();

            return ActivityLog::create([
                'log_name' => $this->logName,
                'description' => $description,
                'subject_type' => $this->subject ? get_class($this->subject) : null,
                'subject_id' => $this->subject ? (string) $this->subject->getKey() : null,
                'causer_type' => $causerId ? 'App\Models\User' : null,
                'causer_id' => $causerId ? (string) $causerId : null,
                'properties' => ! empty($this->properties) ? $this->properties : null,
                'event' => $event,
                'batch_uuid' => null,
                'project_id' => $this->detectProjectId($this->subject),
            ]);
        } catch (\Throwable $e) {
            writeErrorLog('AuditLog write failed', [
                'error' => $e->getMessage(),
                'subject_type' => $this->subject ? get_class($this->subject) : 'none',
                'subject_id' => $this->subject ? (string) $this->subject->getKey() : 'none',
            ]);

            return null;
        }
    }
}
