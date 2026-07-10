<?php

namespace LaraGram\Support\Uri;

enum SchemeType
{
    case Opaque;
    case Hierarchical;
    case Unknown;

    public function isOpaque(): bool
    {
        return self::Opaque === $this;
    }

    public function isHierarchical(): bool
    {
        return self::Hierarchical === $this;
    }

    public function isUnknown(): bool
    {
        return self::Unknown === $this;
    }
}
