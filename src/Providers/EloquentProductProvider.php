<?php

namespace Torob\Laravel\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Torob\Laravel\Contracts\ProductCursorResult;
use Torob\Laravel\Contracts\ProductPageResult;
use Torob\Laravel\Contracts\TorobProductContract;
use Torob\Laravel\Contracts\TorobProductProviderContract;

class EloquentProductProvider implements TorobProductProviderContract
{
    /**
     * @param class-string<Model&TorobProductContract> $modelClass
     */
    public function __construct(
        protected string $modelClass
    ) {
        if (!is_subclass_of($this->modelClass, Model::class)) {
            throw new InvalidArgumentException("{$this->modelClass} must be an Eloquent Model");
        }

        if (!is_subclass_of($this->modelClass, TorobProductContract::class)) {
            throw new InvalidArgumentException("{$this->modelClass} must implement TorobProductContract");
        }
    }

    protected function newQuery(): Builder
    {
        return forward_static_call([$this->modelClass, 'query']);
    }

    public function getByUrls(array $urls): array
    {
        $products = $this->newQuery()->get();
        return $products->filter(function (TorobProductContract $p) use ($urls) {
            return in_array($p->getTorobPageUrl(), $urls, true);
        })->values()->all();
    }

    public function getByUniques(array $uniques): array
    {
        $model = new $this->modelClass();
        $keyName = $model->getKeyName();

        // Check if unique corresponds to primary key
        $products = $this->newQuery()->whereIn($keyName, $uniques)->get();
        if ($products->count() === count($uniques)) {
            return $products->all();
        }

        // Fallback to checking getTorobPageUnique() across candidates
        $all = $this->newQuery()->get();
        return $all->filter(function (TorobProductContract $p) use ($uniques) {
            return in_array($p->getTorobPageUnique(), $uniques, true);
        })->values()->all();
    }

    public function getPaginated(int $page, string $sort): ProductPageResult
    {
        $query = $this->newQuery();

        match ($sort) {
            'date_added_desc' => $query->latest('created_at'),
            'date_updated_desc' => $query->latest('updated_at'),
            'product_id_desc' => $query->latest((new $this->modelClass())->getKeyName()),
            default => $query->latest(),
        };

        $paginator = $query->paginate(100, ['*'], 'page', $page);

        return new ProductPageResult(
            products: $paginator->items(),
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            maxPages: $paginator->lastPage()
        );
    }

    public function getCursor(?string $cursor, string $sort): ProductCursorResult
    {
        $model = new $this->modelClass();
        $keyName = $model->getKeyName();

        $query = $this->newQuery()->orderByDesc($keyName)->limit(100);

        if ($cursor !== null) {
            $query->where($keyName, '<', $cursor);
        }

        $items = $query->get();
        $nextCursor = $items->count() === 100 ? (string) $items->last()->getKey() : null;

        return new ProductCursorResult(
            products: $items->all(),
            nextCursor: $nextCursor
        );
    }
}
