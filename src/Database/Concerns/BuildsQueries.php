<?php

namespace LaraGram\Database\Concerns;

use LaraGram\Database\MultipleRecordsFoundException;
use LaraGram\Database\RecordNotFoundException;
use LaraGram\Database\RecordsNotFoundException;
use LaraGram\Support\Collection;
use LaraGram\Support\LazyCollection;
use LaraGram\Support\Traits\Conditionable;
use InvalidArgumentException;
use RuntimeException;

/**
 * @template TValue
 *
 * @mixin \LaraGram\Database\Eloquent\Builder
 * @mixin \LaraGram\Database\Query\Builder
 */
trait BuildsQueries
{
    use Conditionable;

    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  callable(\LaraGram\Support\Collection<int, TValue>, int): mixed  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $this->enforceOrderBy();

        $page = 1;

        do {
            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            $results = $this->forPage($page, $count)->get();

            $countResults = $results->count();

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);

            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Run a map over each item while chunking.
     *
     * @template TReturn
     *
     * @param  callable(TValue): TReturn  $callback
     * @param  int  $count
     * @return \LaraGram\Support\Collection<int, TReturn>
     */
    public function chunkMap(callable $callback, $count = 1000)
    {
        $collection = new Collection;

        $this->chunk($count, function ($items) use ($collection, $callback) {
            $items->each(function ($item) use ($collection, $callback) {
                $collection->push($callback($item));
            });
        });

        return $collection;
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * @param  callable(TValue, int): mixed  $callback
     * @param  int  $count
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function each(callable $callback, $count = 1000)
    {
        return $this->chunk($count, function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Chunk the results of a query by comparing IDs.
     *
     * @param  int  $count
     * @param  callable(\LaraGram\Support\Collection<int, TValue>, int): mixed  $callback
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = null, $alias = null)
    {
        return $this->orderedChunkById($count, $callback, $column, $alias);
    }

    /**
     * Chunk the results of a query by comparing IDs in descending order.
     *
     * @param  int  $count
     * @param  callable(\LaraGram\Support\Collection<int, TValue>, int): mixed  $callback
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return bool
     */
    public function chunkByIdDesc($count, callable $callback, $column = null, $alias = null)
    {
        return $this->orderedChunkById($count, $callback, $column, $alias, descending: true);
    }

    /**
     * Chunk the results of a query by comparing IDs in a given order.
     *
     * @param  int  $count
     * @param  callable(\LaraGram\Support\Collection<int, TValue>, int): mixed  $callback
     * @param  string|null  $column
     * @param  string|null  $alias
     * @param  bool  $descending
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function orderedChunkById($count, callable $callback, $column = null, $alias = null, $descending = false)
    {
        $column ??= $this->defaultKeyName();

        $alias ??= $column;

        $lastId = null;

        $page = 1;

        do {
            $clone = clone $this;

            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            if ($descending) {
                $results = $clone->forPageBeforeId($count, $lastId, $column)->get();
            } else {
                $results = $clone->forPageAfterId($count, $lastId, $column)->get();
            }

            $countResults = $results->count();

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results, $page) === false) {
                return false;
            }

            $lastId = data_get($results->last(), $alias);

            if ($lastId === null) {
                throw new RuntimeException("The chunkById operation was aborted because the [{$alias}] column is not present in the query result.");
            }

            unset($results);

            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Execute a callback over each item while chunking by ID.
     *
     * @param  callable(TValue, int): mixed  $callback
     * @param  int  $count
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return bool
     */
    public function eachById(callable $callback, $count = 1000, $column = null, $alias = null)
    {
        return $this->chunkById($count, function ($results, $page) use ($callback, $count) {
            foreach ($results as $key => $value) {
                if ($callback($value, (($page - 1) * $count) + $key) === false) {
                    return false;
                }
            }
        }, $column, $alias);
    }

    /**
     * Query lazily, by chunks of the given size.
     *
     * @param  int  $chunkSize
     * @return \LaraGram\Support\LazyCollection
     *
     * @throws \InvalidArgumentException
     */
    public function lazy($chunkSize = 1000)
    {
        if ($chunkSize < 1) {
            throw new InvalidArgumentException('The chunk size should be at least 1');
        }

        $this->enforceOrderBy();

        return LazyCollection::make(function () use ($chunkSize) {
            $page = 1;

            while (true) {
                $results = $this->forPage($page++, $chunkSize)->get();

                foreach ($results as $result) {
                    yield $result;
                }

                if ($results->count() < $chunkSize) {
                    return;
                }
            }
        });
    }

    /**
     * Query lazily, by chunking the results of a query by comparing IDs.
     *
     * @param  int  $chunkSize
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return \LaraGram\Support\LazyCollection
     *
     * @throws \InvalidArgumentException
     */
    public function lazyById($chunkSize = 1000, $column = null, $alias = null)
    {
        return $this->orderedLazyById($chunkSize, $column, $alias);
    }

    /**
     * Query lazily, by chunking the results of a query by comparing IDs in descending order.
     *
     * @param  int  $chunkSize
     * @param  string|null  $column
     * @param  string|null  $alias
     * @return \LaraGram\Support\LazyCollection
     *
     * @throws \InvalidArgumentException
     */
    public function lazyByIdDesc($chunkSize = 1000, $column = null, $alias = null)
    {
        return $this->orderedLazyById($chunkSize, $column, $alias, true);
    }

    /**
     * Query lazily, by chunking the results of a query by comparing IDs in a given order.
     *
     * @param  int  $chunkSize
     * @param  string|null  $column
     * @param  string|null  $alias
     * @param  bool  $descending
     * @return \LaraGram\Support\LazyCollection
     *
     * @throws \InvalidArgumentException
     */
    protected function orderedLazyById($chunkSize = 1000, $column = null, $alias = null, $descending = false)
    {
        if ($chunkSize < 1) {
            throw new InvalidArgumentException('The chunk size should be at least 1');
        }

        $column ??= $this->defaultKeyName();

        $alias ??= $column;

        return LazyCollection::make(function () use ($chunkSize, $column, $alias, $descending) {
            $lastId = null;

            while (true) {
                $clone = clone $this;

                if ($descending) {
                    $results = $clone->forPageBeforeId($chunkSize, $lastId, $column)->get();
                } else {
                    $results = $clone->forPageAfterId($chunkSize, $lastId, $column)->get();
                }

                foreach ($results as $result) {
                    yield $result;
                }

                if ($results->count() < $chunkSize) {
                    return;
                }

                $lastId = $results->last()->{$alias};

                if ($lastId === null) {
                    throw new RuntimeException("The lazyById operation was aborted because the [{$alias}] column is not present in the query result.");
                }
            }
        });
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array|string  $columns
     * @return TValue|null
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array|string  $columns
     * @param  string|null  $message
     * @return TValue
     *
     * @throws \LaraGram\Database\RecordNotFoundException
     */
    public function firstOrFail($columns = ['*'], $message = null)
    {
        if (! is_null($result = $this->first($columns))) {
            return $result;
        }

        throw new RecordNotFoundException($message ?: 'No record found for the given query.');
    }

    /**
     * Execute the query and get the first result if it's the sole matching record.
     *
     * @param  array|string  $columns
     * @return TValue
     *
     * @throws \LaraGram\Database\RecordsNotFoundException
     * @throws \LaraGram\Database\MultipleRecordsFoundException
     */
    public function sole($columns = ['*'])
    {
        $result = $this->take(2)->get($columns);

        $count = $result->count();

        if ($count === 0) {
            throw new RecordsNotFoundException;
        }

        if ($count > 1) {
            throw new MultipleRecordsFoundException($count);
        }

        return $result->first();
    }

    /**
     * Pass the query to a given callback.
     *
     * @param  callable($this): mixed  $callback
     * @return $this
     */
    public function tap($callback)
    {
        $callback($this);

        return $this;
    }
}
