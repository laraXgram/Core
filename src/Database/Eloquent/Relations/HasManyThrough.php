<?php

namespace LaraGram\Database\Eloquent\Relations;

use LaraGram\Database\Eloquent\Builder;
use LaraGram\Database\Eloquent\Collection as EloquentCollection;
use LaraGram\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;

/**
 * @template TRelatedModel of \LaraGram\Database\Eloquent\Model
 * @template TIntermediateModel of \LaraGram\Database\Eloquent\Model
 * @template TDeclaringModel of \LaraGram\Database\Eloquent\Model
 *
 * @extends \LaraGram\Database\Eloquent\Relations\HasOneOrManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel, \LaraGram\Database\Eloquent\Collection<int, TRelatedModel>>
 */
class HasManyThrough extends HasOneOrManyThrough
{
    use InteractsWithDictionary;

    /**
     * Convert the relationship to a "has one through" relationship.
     *
     * @return \LaraGram\Database\Eloquent\Relations\HasOneThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     */
    public function one()
    {
        return HasOneThrough::noConstraints(fn () => new HasOneThrough(
            tap($this->getQuery(), fn (Builder $query) => $query->getQuery()->joins = []),
            $this->farParent,
            $this->throughParent,
            $this->getFirstKeyName(),
            $this->secondKey,
            $this->getLocalKeyName(),
            $this->getSecondLocalKeyName(),
        ));
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /** @inheritDoc */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $this->getDictionaryKey($model->getAttribute($this->localKey))])) {
                $model->setRelation(
                    $relation, $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /** @inheritDoc */
    public function getResults()
    {
        return ! is_null($this->farParent->{$this->localKey})
                ? $this->get()
                : $this->related->newCollection();
    }
}
