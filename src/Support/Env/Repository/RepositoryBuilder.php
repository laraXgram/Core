<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Repository;

use LaraGram\Support\Env\Repository\Adapter\AdapterInterface;
use LaraGram\Support\Env\Repository\Adapter\EnvConstAdapter;
use LaraGram\Support\Env\Repository\Adapter\GuardedWriter;
use LaraGram\Support\Env\Repository\Adapter\ImmutableWriter;
use LaraGram\Support\Env\Repository\Adapter\MultiReader;
use LaraGram\Support\Env\Repository\Adapter\MultiWriter;
use LaraGram\Support\Env\Repository\Adapter\ReaderInterface;
use LaraGram\Support\Env\Repository\Adapter\ServerConstAdapter;
use LaraGram\Support\Env\Repository\Adapter\WriterInterface;
use InvalidArgumentException;
use LaraGram\Support\Env\Util\Some;
use ReflectionClass;

final class RepositoryBuilder
{
    /**
     * The set of default adapters.
     */
    private const DEFAULT_ADAPTERS = [
        ServerConstAdapter::class,
        EnvConstAdapter::class,
    ];

    /**
     * The set of readers to use.
     *
     * @var \LaraGram\Support\Env\Repository\Adapter\ReaderInterface[]
     */
    private $readers;

    /**
     * The set of writers to use.
     *
     * @var \LaraGram\Support\Env\Repository\Adapter\WriterInterface[]
     */
    private $writers;

    /**
     * Are we immutable?
     *
     * @var bool
     */
    private $immutable;

    /**
     * The variable name allow list.
     *
     * @var string[]|null
     */
    private $allowList;

    /**
     * Create a new repository builder instance.
     *
     * @param \LaraGram\Support\Env\Repository\Adapter\ReaderInterface[] $readers
     * @param \LaraGram\Support\Env\Repository\Adapter\WriterInterface[] $writers
     * @param bool                                         $immutable
     * @param string[]|null                                $allowList
     *
     * @return void
     */
    private function __construct(array $readers = [], array $writers = [], bool $immutable = false, ?array $allowList = null)
    {
        $this->readers = $readers;
        $this->writers = $writers;
        $this->immutable = $immutable;
        $this->allowList = $allowList;
    }

    /**
     * Create a new repository builder instance with no adapters added.
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryBuilder
     */
    public static function createWithNoAdapters()
    {
        return new self();
    }

    /**
     * Create a new repository builder instance with the default adapters added.
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryBuilder
     */
    public static function createWithDefaultAdapters()
    {
        $adapters = \iterator_to_array(self::defaultAdapters());

        return new self($adapters, $adapters);
    }

    /**
     * Return the array of default adapters.
     *
     * @return \Generator<\LaraGram\Support\Env\Repository\Adapter\AdapterInterface>
     */
    private static function defaultAdapters()
    {
        foreach (self::DEFAULT_ADAPTERS as $adapter) {
            $instance = $adapter::create();
            if ($instance->isDefined()) {
                yield $instance->get();
            }
        }
    }

    /**
     * Determine if the given name if of an adapterclass.
     *
     * @param string $name
     *
     * @return bool
     */
    private static function isAnAdapterClass(string $name)
    {
        if (!\class_exists($name)) {
            return false;
        }

        return (new ReflectionClass($name))->implementsInterface(AdapterInterface::class);
    }

    /**
     * Creates a repository builder with the given reader added.
     *
     * Accepts either a reader instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it.
     *
     * @param \LaraGram\Support\Env\Repository\Adapter\ReaderInterface|string $reader
     *
     * @throws \InvalidArgumentException
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryBuilder
     */
    public function addReader($reader)
    {
        if (!(\is_string($reader) && self::isAnAdapterClass($reader)) && !($reader instanceof ReaderInterface)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Expected either an instance of %s or a class-string implementing %s',
                    ReaderInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($reader)->flatMap(static function ($reader) {
            return \is_string($reader) ? $reader::create() : Some::create($reader);
        });

        $readers = \array_merge($this->readers, \iterator_to_array($optional));

        return new self($readers, $this->writers, $this->immutable, $this->allowList);
    }

    /**
     * Creates a repository builder with the given writer added.
     *
     * Accepts either a writer instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it.
     *
     * @param \LaraGram\Support\Env\Repository\Adapter\WriterInterface|string $writer
     *
     * @throws \InvalidArgumentException
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryBuilder
     */
    public function addWriter($writer)
    {
        if (!(\is_string($writer) && self::isAnAdapterClass($writer)) && !($writer instanceof WriterInterface)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Expected either an instance of %s or a class-string implementing %s',
                    WriterInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($writer)->flatMap(static function ($writer) {
            return \is_string($writer) ? $writer::create() : Some::create($writer);
        });

        $writers = \array_merge($this->writers, \iterator_to_array($optional));

        return new self($this->readers, $writers, $this->immutable, $this->allowList);
    }

    /**
     * Creates a repository builder with the given adapter added.
     *
     * Accepts either an adapter instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it. We will
     * add the adapter as both a reader and a writer.
     *
     * @param \LaraGram\Support\Env\Repository\Adapter\WriterInterface|string $adapter
     *
     * @throws \InvalidArgumentException
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryBuilder
     */
    public function addAdapter($adapter)
    {
        if (!(\is_string($adapter) && self::isAnAdapterClass($adapter)) && !($adapter instanceof AdapterInterface)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Expected either an instance of %s or a class-string implementing %s',
                    WriterInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($adapter)->flatMap(static function ($adapter) {
            return \is_string($adapter) ? $adapter::create() : Some::create($adapter);
        });

        $readers = \array_merge($this->readers, \iterator_to_array($optional));
        $writers = \array_merge($this->writers, \iterator_to_array($optional));

        return new self($readers, $writers, $this->immutable, $this->allowList);
    }

    /**
     * Creates a repository builder with mutability enabled.
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryBuilder
     */
    public function immutable()
    {
        return new self($this->readers, $this->writers, true, $this->allowList);
    }

    /**
     * Creates a repository builder with the given allow list.
     *
     * @param string[]|null $allowList
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryBuilder
     */
    public function allowList(?array $allowList = null)
    {
        return new self($this->readers, $this->writers, $this->immutable, $allowList);
    }

    /**
     * Creates a new repository instance.
     *
     * @return \LaraGram\Support\Env\Repository\RepositoryInterface
     */
    public function make()
    {
        $reader = new MultiReader($this->readers);
        $writer = new MultiWriter($this->writers);

        if ($this->immutable) {
            $writer = new ImmutableWriter($writer, $reader);
        }

        if ($this->allowList !== null) {
            $writer = new GuardedWriter($writer, $this->allowList);
        }

        return new AdapterRepository($reader, $writer);
    }
}
