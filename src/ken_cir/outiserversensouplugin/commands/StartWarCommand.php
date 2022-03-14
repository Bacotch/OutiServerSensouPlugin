<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

class StartWarCommand extends BaseCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin, "startwar", "");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("id", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§a[システム] このコマンドはサーバー内で実行してください");
            return;
        }

        $warData = WarDataManager::getInstance()->get($args["id"]);
        if (!$warData or !$warData->isStarted()) {
            $warData->setStartDay((int)date("d"));
            $warData->setStartHour((int)date("H"));
            $warData->setStartMinutes((int)date("i"));
            $sender->sendMessage("強制開始");
        }
    }
}