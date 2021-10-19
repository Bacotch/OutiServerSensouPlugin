<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Error;
use Exception;

use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;

use pocketmine\command\CommandSender;
use pocketmine\Player;

final class SetChatModeCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "setchatmode", "チャットモードを設定する", "/setchatmode [モード]");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        try {
            if (!$sender instanceof Player) {
                $this->CommandNotPlayer($sender);
                return;
            }

            if (!isset($args[0])) {
                $sender->sendMessage($this->usageMessage);
                return;
            }

            $player = $sender->getPlayer();
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            if (!$player_data) return;

            if ($args[0] === "全体") {
                $player_data->setChatmode("全体");
                $sender->sendMessage("§a[システム] §fチャットモードを 全体 に切り替えました");

            } else {
                $faction_data = FactionDataManager::getInstance()->get($args[0]);
                if (!$faction_data) {
                    $sender->sendMessage("派閥 $args[0] は存在しません");
                } elseif ($player_data->getFaction() !== $args[0]) {
                    $sender->sendMessage("あなたは派閥 $args[0] に所属していません");
                } else {
                    $player_data->setChatmode($faction_data->getName());
                    $sender->sendMessage("チャットモードを派閥 $args[0] のチャットに切り替えました");
                }
            }
        } catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}