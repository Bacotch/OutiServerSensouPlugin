<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\scheduler\Task;

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

    public function onRun(int $currentTick)
    {
        try {
            call_user_func_array($this->callable, $this->args);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}