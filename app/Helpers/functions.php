<?php

declare(strict_types=1);

function version(): string
{
    return trim(file_get_contents(dirname(__DIR__, 2) . '/VERSION'));
}

function build_hash(): string
{
    return strtolower(md5((string)time()));
}
