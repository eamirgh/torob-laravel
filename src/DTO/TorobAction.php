<?php

namespace Torob\Laravel\DTO;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use Torob\Laravel\Contracts\TorobActionContract;

class TorobAction implements Arrayable
{
    public function __construct(
        public string $timestamp,
        public string $lastUpdatedTimestamp,
        public string $torobClid,
        public string $status,
        public string $actionType,
    ) {
        if (!in_array($this->status, ['completed', 'cancelled'], true)) {
            throw new InvalidArgumentException("Status must be either 'completed' or 'cancelled', got '{$this->status}'");
        }

        if (empty($this->torobClid)) {
            throw new InvalidArgumentException('torobClid cannot be empty');
        }

        if (empty($this->actionType)) {
            throw new InvalidArgumentException('actionType cannot be empty');
        }
    }

    public static function from(mixed $item): self
    {
        if ($item instanceof self) {
            return $item;
        }

        if ($item instanceof TorobActionContract) {
            return new self(
                timestamp: self::formatTimestamp($item->getTorobTimestamp()),
                lastUpdatedTimestamp: self::formatTimestamp($item->getTorobLastUpdatedTimestamp()),
                torobClid: $item->getTorobClid(),
                status: $item->getTorobStatus(),
                actionType: $item->getTorobActionType()
            );
        }

        if (is_array($item)) {
            return self::fromArray($item);
        }

        throw new InvalidArgumentException('Cannot convert value to TorobAction');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: (string) ($data['timestamp'] ?? ''),
            lastUpdatedTimestamp: (string) ($data['last_updated_timestamp'] ?? ''),
            torobClid: (string) ($data['torob_clid'] ?? ''),
            status: (string) ($data['status'] ?? 'completed'),
            actionType: (string) ($data['action_type'] ?? ''),
        );
    }

    protected static function formatTimestamp(string|DateTimeInterface|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format(DateTimeInterface::ATOM);
        }

        return $date;
    }

    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'last_updated_timestamp' => $this->lastUpdatedTimestamp,
            'torob_clid' => $this->torobClid,
            'status' => $this->status,
            'action_type' => $this->actionType,
        ];
    }
}
