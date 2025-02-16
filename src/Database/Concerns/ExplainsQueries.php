<?php

namespace LaraGram\Database\Concerns;

use LaraGram\Support\Collection;

trait ExplainsQueries
{
    /**
     * Explains the query.
     *
     * @return \LaraGram\Support\Collection
     */
    public function explain()
    {
        $sql = $this->toSql();

        $bindings = $this->getBindings();

        $explanation = $this->getConnection()->select('EXPLAIN '.$sql, $bindings);

        return new Collection($explanation);
    }
}
