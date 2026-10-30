<?php

namespace Torob\Laravel\Traits;

use DateTimeInterface;

/**
 * Default implementations of TorobActionContract for Eloquent models.
 */
trait HasTorobAction
{
    public function getTorobTimestamp(): string|DateTimeInterface
    {
        return $this->timestamp ?? $this->created_at ?? date(DATE_ATOM);
    }

    public function getTorobLastUpdatedTimestamp(): string|DateTimeInterface
    {
        return $this->last_updated_timestamp ?? $this->updated_at ?? $this->getTorobTimestamp();
    }

    public function getTorobClid(): string
    {
        return (string) ($this->torob_clid ?? '');
    }

    public function getTorobStatus(): string
    {
        $status = strtolower((string) ($this->status ?? 'completed'));
        return in_array($status, ['cancelled', 'canceled'], true) ? 'cancelled' : 'completed';
    }

    public function getTorobActionType(): string
    {
        return (string) ($this->action_type ?? $this->type ?? 'action');
    }
}
