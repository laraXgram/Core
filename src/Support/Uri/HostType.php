<?php

namespace LaraGram\Support\Uri;

enum HostType
{
    case RegisteredName;
    case Ipv4;
    case Ipv6;
    case IpvFuture;
}
