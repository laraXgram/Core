<?php

namespace LaraGram\Database\Eloquent\Relations;

use LaraGram\Database\Eloquent\Builder;
use LaraGram\Database\Eloquent\Model;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;

/**
 * @template TRelatedModel of \LaraGram\Database\Eloquent\Model
 * @template TDeclaringModel of \LaraGram\Database\Eloquent\Model
 *
 * @extends \LaraGram\Database\Eloquent\Relations\BelongsToMany<TRelatedModel, TDeclaringModel>
 */
class MorphToMany extends BelongsToMany
{
    /**
     * The type of the polymorphic relation.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The class name of the morph type constraint.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Indicates if we are connecting the inverse of the relation.
     *
     * This primarily affects the morphClass constraint.
     *
     * @var bool
     */
    protected $inverse;

    /**
     * Create a new morph to many relationship instance.
     *
     * @param  \LaraGram\Database\Eloquent\Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @param  bool  $inverse
     * @return void
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
        $inverse = false,
    ) {
        $this->inverse = $inverse;
        $this->morphType = $name.'_type';
        $this->morphClass = $inverse ? $query->getModel()->getMorphClass() : $parent->getMorphClass();

        parent::__construct(
            $query, $parent, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, $relationName
        );
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        parent::addWhereConstraints();

        $this->query->where($this->qualifyPivotColumn($this->morphType), $this->morphClass);

        return $this;
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->query->where($this->qualifyPivotColumn($this->morphType), $this->morphClass);
    }

    /**
     * Create a new pivot attachment record.
     *
     * @param  int  $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        return Arr::add(
            parent::baseAttachRecord($id, $timed), $this->morphType, $this->morphClass
        );
    }

    /** @inheritDoc */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $this->qualifyPivotColumn($this->morphType), $this->morphClass
        );
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return \LaraGram\Support\Collection<int, \LaraGram\Database\Eloquent\Relations\Pivot|\LaraGram\Database\Eloquent\Relations\MorphPivot>
     */
    protected function getCurrentlyAttachedPivots()
    {
        return parent::getCurrentlyAttachedPivots()->map(function ($record) {
            return $record instanceof MorphPivot
                            ? $record->setMorphType($this->morphType)
                                     ->setMorphClass($this->morphClass)
                            : $record;
        });
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \LaraGram\Database\Query\Builder
     */
    public function newPivotQuery()
    {
        return parent::newPivotQuery()->where($this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot model instance.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return \LaraGram\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        $using = $this->using;

        $attributes = array_merge([$this->morphType => $this->morphClass], $attributes);

        $pivot = $using ? $using::fromRawAttributes($this->parent, $attributes, $this->table, $exists)
                        : MorphPivot::fromAttributes($this->parent, $attributes, $this->table, $exists);

        $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey)
              ->setRelatedModel($this->related)
              ->setMorphType($this->morphType)
              ->setMorphClass($this->morphClass);

        return $pivot;
    }

    /**
     * Get the pivot columns for the relation.
     *
     * "pivot_" is prefixed at each column for easy removal later.
     *
     * @return array
     */
    protected function aliasedPivotColumns()
    {
        $defaults = [$this->foreignPivotKey, $this->relatedPivotKey, $this->morphType];

        return (new Collection(array_merge($defaults, $this->pivotColumns)))->map(function ($column) {
            return $this->qualifyPivotColumn($column).' as pivot_'.$column;
        })->unique()->all();
    }

    /**
     * Get the foreign key "type" name.
     *
     * @return string
     */
    public function getMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the fully qualified morph type for the relation.
     *
     * @return string
     */
    public function getQualifiedMorphTypeName()
    {
        return $this->qualifyPivotColumn($this->morphType);
    }

    /**
     * Get the class name of the parent model.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }

    /**
     * Get the indicator for a reverse relationship.
     *
     * @return bool
     */
    public function getInverse()
    {
        return $this->inverse;
    }
}
