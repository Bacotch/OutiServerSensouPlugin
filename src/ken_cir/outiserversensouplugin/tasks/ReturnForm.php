<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use pocketmine\scheduler\Task;
use function call_user_func_array;

/**
 * 一定時間後フォームに移動させる用のTask
 */
final class ReturnForm extends Task
{
    private $callable;
    private array $args;

    public function __construct(callable $callable, array $args = [])
    {
        $this->callable = $callable;
        $this->args = $args;
    }

    /**
     * 実行
     */
    public function onRun(): void
    {
        call_user_func_array($this->callable, $this->args);
    }
}