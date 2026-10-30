<?php

namespace Torob\Laravel\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Torob\Laravel\Contracts\TorobOrderContract;
use Torob\Laravel\Contracts\TorobOrderProviderContract;

class EloquentOrderProvider implements TorobOrderProviderContract
{
    /**
     * @param class-string<Model&TorobOrderContract> $modelClass
     */
    public function __construct(
        protected string $modelClass,
        protected bool $enabled = true
    ) {
        if (!is_subclass_of($this->modelClass, Model::class)) {
            throw new InvalidArgumentException("{$this->modelClass} must be an Eloquent Model");
        }

        if (!is_subclass_of($this->modelClass, TorobOrderContract::class)) {
            throw new InvalidArgumentException("{$this->modelClass} must implement TorobOrderContract");
        }
    }

    protected function newQuery(): Builder
    {
        return forward_static_call([$this->modelClass, 'query']);
    }

    public function isOrderTrackingEnabled(): bool
    {
        return $this->enabled;
    }

    public function getOrders(string $purchaseTimestampGt, int $limit): array
    {
        $timestamp = strtotime($purchaseTimestampGt);
        $date = $timestamp ? date('Y-m-d H:i:s', $timestamp) : $purchaseTimestampGt;

        return $this->newQuery()
            ->whereNotNull('torob_clid')
            ->where('created_at', '>', $date)
            ->oldest('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }
}
