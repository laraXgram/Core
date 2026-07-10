<?php

namespace LaraGram\Contracts\JsonSchema;

use Closure;

interface JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchema): array<string, \LaraGram\JsonSchema\Types\Type>)|array<string, \LaraGram\JsonSchema\Types\Type>  $properties
     * @return \LaraGram\JsonSchema\Types\ObjectType
     */
    public function object(Closure|array $properties = []);

    /**
     * Create a new array property instance.
     *
     * @return \LaraGram\JsonSchema\Types\ArrayType
     */
    public function array();

    /**
     * Create a new string property instance.
     *
     * @return \LaraGram\JsonSchema\Types\StringType
     */
    public function string();

    /**
     * Create a new integer property instance.
     *
     * @return \LaraGram\JsonSchema\Types\IntegerType
     */
    public function integer();

    /**
     * Create a new number property instance.
     *
     * @return \LaraGram\JsonSchema\Types\NumberType
     */
    public function number();

    /**
     * Create a new boolean property instance.
     *
     * @return \LaraGram\JsonSchema\Types\BooleanType
     */
    public function boolean();

    /**
     * Create a new multi-type union instance.
     *
     * @param  array<int, string>  $types
     * @return \LaraGram\JsonSchema\Types\UnionType
     */
    public function union(array $types);

    /**
     * Create a new anyOf schema instance.
     *
     * @param  (Closure(JsonSchema): array<int, \LaraGram\JsonSchema\Types\Type>)|array<int, \LaraGram\JsonSchema\Types\Type>  $schemas
     * @return \LaraGram\JsonSchema\Types\AnyOfType
     */
    public function anyOf(Closure|array $schemas);
}
