<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use function register_shutdown_function;
use function unlink;
use function rename;
use function pcntl_exec;

class RestartCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "restart", "サーバーを再起動するコマンド");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        // シャットダウン関数を登録
        register_shutdown_function(function () {
            pcntl_exec("./start.sh");
        });

        Server::getInstance()->shutdown();
    }
}