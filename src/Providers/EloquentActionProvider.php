<?php

namespace Torob\Laravel\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Torob\Laravel\Contracts\TorobActionContract;
use Torob\Laravel\Contracts\TorobActionProviderContract;

class EloquentActionProvider implements TorobActionProviderContract
{
    /**
     * @param class-string<Model&TorobActionContract> $modelClass
     */
    public function __construct(
        protected string $modelClass,
        protected bool $enabled = true
    ) {
        if (!is_subclass_of($this->modelClass, Model::class)) {
            throw new InvalidArgumentException("{$this->modelClass} must be an Eloquent Model");
        }

        if (!is_subclass_of($this->modelClass, TorobActionContract::class)) {
            throw new InvalidArgumentException("{$this->modelClass} must implement TorobActionContract");
        }
    }

    protected function newQuery(): Builder
    {
        return forward_static_call([$this->modelClass, 'query']);
    }

    public function isActionTrackingEnabled(): bool
    {
        return $this->enabled;
    }

    public function getActions(string $timestampGt, int $limit): array
    {
        $timestamp = strtotime($timestampGt);
        $date = $timestamp ? date('Y-m-d H:i:s', $timestamp) : $timestampGt;

        return $this->newQuery()
            ->whereNotNull('torob_clid')
            ->where('created_at', '>', $date)
            ->oldest('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }
}
