<?php

namespace LaraGram\Listening;

abstract class MatcherDumper implements MatcherDumperInterface
{
    public function __construct(
        private BaseListenCollection $listens,
    ) {
    }

    public function getListens(): BaseListenCollection
    {
        return $this->listens;
    }
}
