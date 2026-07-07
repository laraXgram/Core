<?php

namespace LaraGram\Http\VarDumper\Cloner;

class Stub
{
    public const TYPE_REF = 1;
    public const TYPE_STRING = 2;
    public const TYPE_ARRAY = 3;
    public const TYPE_OBJECT = 4;
    public const TYPE_RESOURCE = 5;
    public const TYPE_SCALAR = 6;

    public const STRING_BINARY = 1;
    public const STRING_UTF8 = 2;

    public const ARRAY_ASSOC = 1;
    public const ARRAY_INDEXED = 2;

    public int $type = self::TYPE_REF;
    public string|int|null $class = '';
    public mixed $value = null;
    public int $cut = 0;
    public int $handle = 0;
    public int $refCount = 0;
    public int $position = 0;
    public array $attr = [];

    /**
     * @internal
     */
    protected static array $propertyDefaults = [];

    public function __serialize(): array
    {
        static $noDefault = new \stdClass();

        if (self::class === static::class) {
            $data = [];
            foreach ($this as $k => $v) {
                $default = self::$propertyDefaults[$this::class][$k] ??= ($p = new \ReflectionProperty($this, $k))->hasDefaultValue() ? $p->getDefaultValue() : ($p->hasType() ? $noDefault : null);
                if ($noDefault === $default || $default !== $v) {
                    $data[$k] = $v;
                }
            }

            return $data;
        }

        return \Closure::bind(function () use ($noDefault) {
            $data = [];
            foreach ($this as $k => $v) {
                $default = self::$propertyDefaults[$this::class][$k] ??= ($p = new \ReflectionProperty($this, $k))->hasDefaultValue() ? $p->getDefaultValue() : ($p->hasType() ? $noDefault : null);
                if ($noDefault === $default || $default !== $v) {
                    $data[$k] = $v;
                }
            }

            return $data;
        }, $this, $this::class)();
    }
}
