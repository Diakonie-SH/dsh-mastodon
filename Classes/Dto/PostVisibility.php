<?php

namespace DiakonieSH\DshMastodon\Dto;

enum PostVisibility: int
{
    case Public = 0;
    case Unlisted = 1;
    case Private = 2;
    case Direct = 3;

    public function asString(): string
    {
        return match ($this) {
            self::Public => 'public',
            self::Unlisted => 'unlisted',
            self::Private => 'private',
            self::Direct => 'direct',
        };
    }
}
