<?php

namespace LaraGram\Database\Eloquent\Relations;

use LaraGram\Database\Eloquent\Collection as EloquentCollection;
use LaraGram\Database\Eloquent\Model;
use LaraGram\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use LaraGram\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

/**
 * @template TRelatedModel of \LaraGram\Database\Eloquent\Model
 * @template TIntermediateModel of \LaraGram\Database\Eloquent\Model
 * @template TDeclaringModel of \LaraGram\Database\Eloquent\Model
 *
 * @extends \LaraGram\Database\Eloquent\Relations\HasOneOrManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel, ?TRelatedModel>
 */
class HasOneThrough extends HasOneOrManyThrough
{
    use InteractsWithDictionary, SupportsDefaultModels;

    /** @inheritDoc */
    public function getResults()
    {
        return $this->first() ?: $this->getDefaultFor($this->farParent);
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
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
                $value = $dictionary[$key];
                $model->setRelation(
                    $relation, reset($value)
                );
            }
        }

        return $models;
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param  TDeclaringModel  $parent
     * @return TRelatedModel
     */
    public function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }
}
