<?php

namespace LaraGram\Database\Eloquent\Factories;

use Closure;
use DateTime;
use Faker\Generator;
use LaraGram\Container\Container;
use LaraGram\Contracts\Foundation\Application;
use LaraGram\Database\Eloquent\Collection as EloquentCollection;
use LaraGram\Database\Eloquent\Model;
use LaraGram\Database\Eloquent\SoftDeletes;
use LaraGram\Support\Collection;
use LaraGram\Support\Enumerable;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\Conditionable;
use LaraGram\Support\Traits\ForwardsCalls;
use LaraGram\Support\Traits\Macroable;
use Throwable;

/**
 * @template TModel of \LaraGram\Database\Eloquent\Model
 *
 * @method $this trashed()
 */
abstract class Factory
{
    use Conditionable, ForwardsCalls, Macroable {
        __call as macroCall;
    }

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model;

    /**
     * The number of models that should be generated.
     *
     * @var int|null
     */
    protected $count;

    /**
     * The state transformations that will be applied to the model.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $states;

    /**
     * The parent relationships that will be applied to the model.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $has;

    /**
     * The child relationships that will be applied to the model.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $for;

    /**
     * The model instances to always use when creating relationships.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $recycle;

    /**
     * The "after making" callbacks that will be applied to the model.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $afterMaking;

    /**
     * The "after creating" callbacks that will be applied to the model.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $afterCreating;

    /**
     * Whether relationships should not be automatically created.
     *
     * @var bool
     */
    protected $expandRelationships = true;

    /**
     * The name of the database connection that will be used to create the models.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * The current Faker instance.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The default namespace where factories reside.
     *
     * @var string
     */
    public static $namespace = 'Database\\Factories\\';

    /**
     * The default model name resolver.
     *
     * @var callable(self): class-string<TModel>
     */
    protected static $modelNameResolver;

    /**
     * The factory name resolver.
     *
     * @var callable
     */
    protected static $factoryNameResolver;

    /**
     * Create a new factory instance.
     *
     * @param  int|null  $count
     * @param  \LaraGram\Support\Collection|null  $states
     * @param  \LaraGram\Support\Collection|null  $has
     * @param  \LaraGram\Support\Collection|null  $for
     * @param  \LaraGram\Support\Collection|null  $afterMaking
     * @param  \LaraGram\Support\Collection|null  $afterCreating
     * @param  string|null  $connection
     * @param  \LaraGram\Support\Collection|null  $recycle
     * @param  bool  $expandRelationships
     * @return void
     */
    public function __construct(
        $count = null,
        ?Collection $states = null,
        ?Collection $has = null,
        ?Collection $for = null,
        ?Collection $afterMaking = null,
        ?Collection $afterCreating = null,
        $connection = null,
        ?Collection $recycle = null,
        bool $expandRelationships = true
    ) {
        $this->count = $count;
        $this->states = $states ?? new Collection;
        $this->has = $has ?? new Collection;
        $this->for = $for ?? new Collection;
        $this->afterMaking = $afterMaking ?? new Collection;
        $this->afterCreating = $afterCreating ?? new Collection;
        $this->connection = $connection;
        $this->recycle = $recycle ?? new Collection;
        $this->faker = $this->withFaker();
        $this->expandRelationships = $expandRelationships;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    abstract public function definition();

    /**
     * Get a new factory instance for the given attributes.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @return static
     */
    public static function new($attributes = [])
    {
        return (new static)->state($attributes)->configure();
    }

    /**
     * Get a new factory instance for the given number of models.
     *
     * @param  int  $count
     * @return static
     */
    public static function times(int $count)
    {
        return static::new()->count($count);
    }

    /**
     * Configure the factory.
     *
     * @return static
     */
    public function configure()
    {
        return $this;
    }

    /**
     * Get the raw attributes generated by the factory.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return array<int|string, mixed>
     */
    public function raw($attributes = [], ?Model $parent = null)
    {
        if ($this->count === null) {
            return $this->state($attributes)->getExpandedAttributes($parent);
        }

        return array_map(function () use ($attributes, $parent) {
            return $this->state($attributes)->getExpandedAttributes($parent);
        }, range(1, $this->count));
    }

    /**
     * Create a single model and persist it to the database.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @return TModel
     */
    public function createOne($attributes = [])
    {
        return $this->count(null)->create($attributes);
    }

    /**
     * Create a single model and persist it to the database without dispatching any model events.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @return TModel
     */
    public function createOneQuietly($attributes = [])
    {
        return $this->count(null)->createQuietly($attributes);
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  int|null|iterable<int, array<string, mixed>>  $records
     * @return \LaraGram\Database\Eloquent\Collection<int, TModel>
     */
    public function createMany(int|iterable|null $records = null)
    {
        $records ??= ($this->count ?? 1);

        $this->count = null;

        if (is_numeric($records)) {
            $records = array_fill(0, $records, []);
        }

        return new EloquentCollection(
            (new Collection($records))->map(function ($record) {
                return $this->state($record)->create();
            })
        );
    }

    /**
     * Create a collection of models and persist them to the database without dispatching any model events.
     *
     * @param  int|null|iterable<int, array<string, mixed>>  $records
     * @return \LaraGram\Database\Eloquent\Collection<int, TModel>
     */
    public function createManyQuietly(int|iterable|null $records = null)
    {
        return Model::withoutEvents(fn () => $this->createMany($records));
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return \LaraGram\Database\Eloquent\Collection<int, TModel>|TModel
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        if (! empty($attributes)) {
            return $this->state($attributes)->create([], $parent);
        }

        $results = $this->make($attributes, $parent);

        if ($results instanceof Model) {
            $this->store(new Collection([$results]));

            $this->callAfterCreating(new Collection([$results]), $parent);
        } else {
            $this->store($results);

            $this->callAfterCreating($results, $parent);
        }

        return $results;
    }

    /**
     * Create a collection of models and persist them to the database without dispatching any model events.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return \LaraGram\Database\Eloquent\Collection<int, TModel>|TModel
     */
    public function createQuietly($attributes = [], ?Model $parent = null)
    {
        return Model::withoutEvents(fn () => $this->create($attributes, $parent));
    }

    /**
     * Create a callback that persists a model in the database when invoked.
     *
     * @param  array<string, mixed>  $attributes
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return \Closure(): (\LaraGram\Database\Eloquent\Collection<int, TModel>|TModel)
     */
    public function lazy(array $attributes = [], ?Model $parent = null)
    {
        return fn () => $this->create($attributes, $parent);
    }

    /**
     * Set the connection name on the results and store them.
     *
     * @param  \LaraGram\Support\Collection<int, \LaraGram\Database\Eloquent\Model>  $results
     * @return void
     */
    protected function store(Collection $results)
    {
        $results->each(function ($model) {
            if (! isset($this->connection)) {
                $model->setConnection($model->newQueryWithoutScopes()->getConnection()->getName());
            }

            $model->save();

            foreach ($model->getRelations() as $name => $items) {
                if ($items instanceof Enumerable && $items->isEmpty()) {
                    $model->unsetRelation($name);
                }
            }

            $this->createChildren($model);
        });
    }

    /**
     * Create the children for the given model.
     *
     * @param  \LaraGram\Database\Eloquent\Model  $model
     * @return void
     */
    protected function createChildren(Model $model)
    {
        Model::unguarded(function () use ($model) {
            $this->has->each(function ($has) use ($model) {
                $has->recycle($this->recycle)->createFor($model);
            });
        });
    }

    /**
     * Make a single instance of the model.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @return TModel
     */
    public function makeOne($attributes = [])
    {
        return $this->count(null)->make($attributes);
    }

    /**
     * Create a collection of models.
     *
     * @param  (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed>  $attributes
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return \LaraGram\Database\Eloquent\Collection<int, TModel>|TModel
     */
    public function make($attributes = [], ?Model $parent = null)
    {
        if (! empty($attributes)) {
            return $this->state($attributes)->make([], $parent);
        }

        if ($this->count === null) {
            return tap($this->makeInstance($parent), function ($instance) {
                $this->callAfterMaking(new Collection([$instance]));
            });
        }

        if ($this->count < 1) {
            return $this->newModel()->newCollection();
        }

        $instances = $this->newModel()->newCollection(array_map(function () use ($parent) {
            return $this->makeInstance($parent);
        }, range(1, $this->count)));

        $this->callAfterMaking($instances);

        return $instances;
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return \LaraGram\Database\Eloquent\Model
     */
    protected function makeInstance(?Model $parent)
    {
        return Model::unguarded(function () use ($parent) {
            return tap($this->newModel($this->getExpandedAttributes($parent)), function ($instance) {
                if (isset($this->connection)) {
                    $instance->setConnection($this->connection);
                }
            });
        });
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return mixed
     */
    protected function getExpandedAttributes(?Model $parent)
    {
        return $this->expandAttributes($this->getRawAttributes($parent));
    }

    /**
     * Get the raw attributes for the model as an array.
     *
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return array
     */
    protected function getRawAttributes(?Model $parent)
    {
        return $this->states->pipe(function ($states) {
            return $this->for->isEmpty() ? $states : new Collection(array_merge([function () {
                return $this->parentResolvers();
            }], $states->all()));
        })->reduce(function ($carry, $state) use ($parent) {
            if ($state instanceof Closure) {
                $state = $state->bindTo($this);
            }

            return array_merge($carry, $state($carry, $parent));
        }, $this->definition());
    }

    /**
     * Create the parent relationship resolvers (as deferred Closures).
     *
     * @return array
     */
    protected function parentResolvers()
    {
        return $this->for
            ->map(fn (BelongsToRelationship $for) => $for->recycle($this->recycle)->attributesFor($this->newModel()))
            ->collapse()
            ->all();
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param  array  $definition
     * @return array
     */
    protected function expandAttributes(array $definition)
    {
        return (new Collection($definition))
            ->map($evaluateRelations = function ($attribute) {
                if (! $this->expandRelationships && $attribute instanceof self) {
                    $attribute = null;
                } elseif ($attribute instanceof self) {
                    $attribute = $this->getRandomRecycledModel($attribute->modelName())?->getKey()
                        ?? $attribute->recycle($this->recycle)->create()->getKey();
                } elseif ($attribute instanceof Model) {
                    $attribute = $attribute->getKey();
                }

                return $attribute;
            })
            ->map(function ($attribute, $key) use (&$definition, $evaluateRelations) {
                if (is_callable($attribute) && ! is_string($attribute) && ! is_array($attribute)) {
                    $attribute = $attribute($definition);
                }

                $attribute = $evaluateRelations($attribute);

                $definition[$key] = $attribute;

                return $attribute;
            })
            ->all();
    }

    /**
     * Add a new state transformation to the model definition.
     *
     * @param  (callable(array<string, mixed>, TModel|null): array<string, mixed>)|array<string, mixed>  $state
     * @return static
     */
    public function state($state)
    {
        return $this->newInstance([
            'states' => $this->states->concat([
                is_callable($state) ? $state : fn () => $state,
            ]),
        ]);
    }

    /**
     * Set a single model attribute.
     *
     * @param  string|int  $key
     * @param  mixed  $value
     * @return static
     */
    public function set($key, $value)
    {
        return $this->state([$key => $value]);
    }

    /**
     * Add a new sequenced state transformation to the model definition.
     *
     * @param  mixed  ...$sequence
     * @return static
     */
    public function sequence(...$sequence)
    {
        return $this->state(new Sequence(...$sequence));
    }

    /**
     * Add a new sequenced state transformation to the model definition and update the pending creation count to the size of the sequence.
     *
     * @param  array  ...$sequence
     * @return static
     */
    public function forEachSequence(...$sequence)
    {
        return $this->state(new Sequence(...$sequence))->count(count($sequence));
    }

    /**
     * Add a new cross joined sequenced state transformation to the model definition.
     *
     * @param  array  ...$sequence
     * @return static
     */
    public function crossJoinSequence(...$sequence)
    {
        return $this->state(new CrossJoinSequence(...$sequence));
    }

    /**
     * Define a child relationship for the model.
     *
     * @param  \LaraGram\Database\Eloquent\Factories\Factory  $factory
     * @param  string|null  $relationship
     * @return static
     */
    public function has(self $factory, $relationship = null)
    {
        return $this->newInstance([
            'has' => $this->has->concat([new Relationship(
                $factory, $relationship ?? $this->guessRelationship($factory->modelName())
            )]),
        ]);
    }

    /**
     * Attempt to guess the relationship name for a "has" relationship.
     *
     * @param  string  $related
     * @return string
     */
    protected function guessRelationship(string $related)
    {
        $guess = Str::camel(Str::plural(class_basename($related)));

        return method_exists($this->modelName(), $guess) ? $guess : Str::singular($guess);
    }

    /**
     * Define an attached relationship for the model.
     *
     * @param  \LaraGram\Database\Eloquent\Factories\Factory|\LaraGram\Support\Collection|\LaraGram\Database\Eloquent\Model|array  $factory
     * @param  (callable(): array<string, mixed>)|array<string, mixed>  $pivot
     * @param  string|null  $relationship
     * @return static
     */
    public function hasAttached($factory, $pivot = [], $relationship = null)
    {
        return $this->newInstance([
            'has' => $this->has->concat([new BelongsToManyRelationship(
                $factory,
                $pivot,
                $relationship ?? Str::camel(Str::plural(class_basename(
                    $factory instanceof Factory
                        ? $factory->modelName()
                        : Collection::wrap($factory)->first()
                )))
            )]),
        ]);
    }

    /**
     * Define a parent relationship for the model.
     *
     * @param  \LaraGram\Database\Eloquent\Factories\Factory|\LaraGram\Database\Eloquent\Model  $factory
     * @param  string|null  $relationship
     * @return static
     */
    public function for($factory, $relationship = null)
    {
        return $this->newInstance(['for' => $this->for->concat([new BelongsToRelationship(
            $factory,
            $relationship ?? Str::camel(class_basename(
                $factory instanceof Factory ? $factory->modelName() : $factory
            ))
        )])]);
    }

    /**
     * Provide model instances to use instead of any nested factory calls when creating relationships.
     *
     * @param  \LaraGram\Database\Eloquent\Model|\LaraGram\Support\Collection|array  $model
     * @return static
     */
    public function recycle($model)
    {
        // Group provided models by the type and merge them into existing recycle collection
        return $this->newInstance([
            'recycle' => $this->recycle
                ->flatten()
                ->merge(
                    Collection::wrap($model instanceof Model ? func_get_args() : $model)
                        ->flatten()
                )->groupBy(fn ($model) => get_class($model)),
        ]);
    }

    /**
     * Retrieve a random model of a given type from previously provided models to recycle.
     *
     * @template TClass of \LaraGram\Database\Eloquent\Model
     *
     * @param  class-string<TClass>  $modelClassName
     * @return TClass|null
     */
    public function getRandomRecycledModel($modelClassName)
    {
        return $this->recycle->get($modelClassName)?->random();
    }

    /**
     * Add a new "after making" callback to the model definition.
     *
     * @param  \Closure(TModel): mixed  $callback
     * @return static
     */
    public function afterMaking(Closure $callback)
    {
        return $this->newInstance(['afterMaking' => $this->afterMaking->concat([$callback])]);
    }

    /**
     * Add a new "after creating" callback to the model definition.
     *
     * @param  \Closure(TModel, \LaraGram\Database\Eloquent\Model|null): mixed  $callback
     * @return static
     */
    public function afterCreating(Closure $callback)
    {
        return $this->newInstance(['afterCreating' => $this->afterCreating->concat([$callback])]);
    }

    /**
     * Call the "after making" callbacks for the given model instances.
     *
     * @param  \LaraGram\Support\Collection  $instances
     * @return void
     */
    protected function callAfterMaking(Collection $instances)
    {
        $instances->each(function ($model) {
            $this->afterMaking->each(function ($callback) use ($model) {
                $callback($model);
            });
        });
    }

    /**
     * Call the "after creating" callbacks for the given model instances.
     *
     * @param  \LaraGram\Support\Collection  $instances
     * @param  \LaraGram\Database\Eloquent\Model|null  $parent
     * @return void
     */
    protected function callAfterCreating(Collection $instances, ?Model $parent = null)
    {
        $instances->each(function ($model) use ($parent) {
            $this->afterCreating->each(function ($callback) use ($model, $parent) {
                $callback($model, $parent);
            });
        });
    }

    /**
     * Specify how many models should be generated.
     *
     * @param  int|null  $count
     * @return static
     */
    public function count(?int $count)
    {
        return $this->newInstance(['count' => $count]);
    }

    /**
     * Indicate that related parent models should not be created.
     *
     * @return static
     */
    public function withoutParents()
    {
        return $this->newInstance(['expandRelationships' => false]);
    }

    /**
     * Get the name of the database connection that is used to generate models.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Specify the database connection that should be used to generate models.
     *
     * @param  string  $connection
     * @return static
     */
    public function connection(string $connection)
    {
        return $this->newInstance(['connection' => $connection]);
    }

    /**
     * Create a new instance of the factory builder with the given mutated properties.
     *
     * @param  array  $arguments
     * @return static
     */
    protected function newInstance(array $arguments = [])
    {
        return new static(...array_values(array_merge([
            'count' => $this->count,
            'states' => $this->states,
            'has' => $this->has,
            'for' => $this->for,
            'afterMaking' => $this->afterMaking,
            'afterCreating' => $this->afterCreating,
            'connection' => $this->connection,
            'recycle' => $this->recycle,
            'expandRelationships' => $this->expandRelationships,
        ], $arguments)));
    }

    /**
     * Get a new model instance.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function newModel(array $attributes = [])
    {
        $model = $this->modelName();

        return new $model($attributes);
    }

    /**
     * Get the name of the model that is generated by the factory.
     *
     * @return class-string<TModel>
     */
    public function modelName()
    {
        if ($this->model !== null) {
            return $this->model;
        }

        $resolver = static::$modelNameResolver ?? function (self $factory) {
            $namespacedFactoryBasename = Str::replaceLast(
                'Factory', '', Str::replaceFirst(static::$namespace, '', get_class($factory))
            );

            $factoryBasename = Str::replaceLast('Factory', '', class_basename($factory));

            $appNamespace = static::appNamespace();

            return class_exists($appNamespace.'Models\\'.$namespacedFactoryBasename)
                        ? $appNamespace.'Models\\'.$namespacedFactoryBasename
                        : $appNamespace.$factoryBasename;
        };

        return $resolver($this);
    }

    /**
     * Specify the callback that should be invoked to guess model names based on factory names.
     *
     * @param  callable(self): class-string<TModel>  $callback
     * @return void
     */
    public static function guessModelNamesUsing(callable $callback)
    {
        static::$modelNameResolver = $callback;
    }

    /**
     * Specify the default namespace that contains the application's model factories.
     *
     * @param  string  $namespace
     * @return void
     */
    public static function useNamespace(string $namespace)
    {
        static::$namespace = $namespace;
    }

    /**
     * Get a new factory instance for the given model name.
     *
     * @template TClass of \LaraGram\Database\Eloquent\Model
     *
     * @param  class-string<TClass>  $modelName
     * @return \LaraGram\Database\Eloquent\Factories\Factory<TClass>
     */
    public static function factoryForModel(string $modelName)
    {
        $factory = static::resolveFactoryName($modelName);

        return $factory::new();
    }

    /**
     * Specify the callback that should be invoked to guess factory names based on dynamic relationship names.
     *
     * @param  callable(class-string<\LaraGram\Database\Eloquent\Model>): class-string<\LaraGram\Database\Eloquent\Factories\Factory>  $callback
     * @return void
     */
    public static function guessFactoryNamesUsing(callable $callback)
    {
        static::$factoryNameResolver = $callback;
    }

    /**
     * Get a new Faker instance.
     *
     * @return \Faker\Generator
     */
    protected function withFaker()
    {
        return Container::getInstance()->make(Generator::class);
    }

    /**
     * Get the factory name for the given model name.
     *
     * @template TClass of \LaraGram\Database\Eloquent\Model
     *
     * @param  class-string<TClass>  $modelName
     * @return class-string<\LaraGram\Database\Eloquent\Factories\Factory<TClass>>
     */
    public static function resolveFactoryName(string $modelName)
    {
        $resolver = static::$factoryNameResolver ?? function (string $modelName) {
            $appNamespace = static::appNamespace();

            $modelName = Str::startsWith($modelName, $appNamespace.'Models\\')
                ? Str::after($modelName, $appNamespace.'Models\\')
                : Str::after($modelName, $appNamespace);

            return static::$namespace.$modelName.'Factory';
        };

        return $resolver($modelName);
    }

    /**
     * Get the application namespace for the application.
     *
     * @return string
     */
    protected static function appNamespace()
    {
        try {
            return Container::getInstance()
                ->make(Application::class)
                ->getNamespace();
        } catch (Throwable) {
            return 'App\\';
        }
    }

    /**
     * Proxy dynamic factory methods onto their proper methods.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if ($method === 'trashed' && in_array(SoftDeletes::class, class_uses_recursive($this->modelName()))) {
            return $this->state([
                $this->newModel()->getDeletedAtColumn() => $parameters[0] ?? (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s'),
            ]);
        }

        if (! Str::startsWith($method, ['for', 'has'])) {
            static::throwBadMethodCallException($method);
        }

        $relationship = Str::camel(Str::substr($method, 3));

        $relatedModel = get_class($this->newModel()->{$relationship}()->getRelated());

        if (method_exists($relatedModel, 'newFactory')) {
            $factory = $relatedModel::newFactory() ?? static::factoryForModel($relatedModel);
        } else {
            $factory = static::factoryForModel($relatedModel);
        }

        if (str_starts_with($method, 'for')) {
            return $this->for($factory->state($parameters[0] ?? []), $relationship);
        } elseif (str_starts_with($method, 'has')) {
            return $this->has(
                $factory
                    ->count(is_numeric($parameters[0] ?? null) ? $parameters[0] : 1)
                    ->state((is_callable($parameters[0] ?? null) || is_array($parameters[0] ?? null)) ? $parameters[0] : ($parameters[1] ?? [])),
                $relationship
            );
        }
    }
}
