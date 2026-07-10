<?php

namespace LaraGram\Http\VarDumper\Cloner;

class Cursor
{
    public const HASH_INDEXED = Stub::ARRAY_INDEXED;
    public const HASH_ASSOC = Stub::ARRAY_ASSOC;
    public const HASH_OBJECT = Stub::TYPE_OBJECT;
    public const HASH_RESOURCE = Stub::TYPE_RESOURCE;

    public int $depth = 0;
    public int $refIndex = 0;
    public int $softRefTo = 0;
    public int $softRefCount = 0;
    public int $softRefHandle = 0;
    public int $hardRefTo = 0;
    public int $hardRefCount = 0;
    public int $hardRefHandle = 0;
    public int $hashType;
    public string|int|null $hashKey = null;
    public bool $hashKeyIsBinary;
    public int $hashIndex = 0;
    public int $hashLength = 0;
    public int $hashCut = 0;
    public bool $stop = false;
    public array $attr = [];
    public bool $skipChildren = false;
}
