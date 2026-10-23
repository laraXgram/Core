<?php

namespace LaraGram\Foundation;

enum DevCommandMode: string
{
    case TABS = 'tabs';
    case STREAM = 'stream';
    case INLINE = 'inline';
}
