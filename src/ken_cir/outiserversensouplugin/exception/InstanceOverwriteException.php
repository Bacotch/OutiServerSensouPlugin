<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\exception;

use JetBrains\PhpStorm\Pure;
use RuntimeException;

/**
 * クラスインスタンスを上書きしようとした時の例外
 */
final class InstanceOverwriteException extends RuntimeException
{
    #[Pure] public function __construct(string $className = "")
    {
        parent::__construct("$className has already been initialized");
    }
}