<?php

namespace Torob\Laravel\Contracts;

use DateTimeInterface;

interface TorobActionContract
{
    /**
     * Initial action timestamp (UTC ISO 8601 or DateTimeInterface).
     */
    public function getTorobTimestamp(): string|DateTimeInterface;

    /**
     * Last modified timestamp (UTC ISO 8601 or DateTimeInterface).
     */
    public function getTorobLastUpdatedTimestamp(): string|DateTimeInterface;

    /**
     * Tracking identifier (torob_clid).
     */
    public function getTorobClid(): string;

    /**
     * Current status ('completed' or 'cancelled').
     */
    public function getTorobStatus(): string;

    /**
     * Action type identifier.
     */
    public function getTorobActionType(): string;
}
