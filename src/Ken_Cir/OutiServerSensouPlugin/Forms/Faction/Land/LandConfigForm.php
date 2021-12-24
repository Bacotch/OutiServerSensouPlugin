<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Land;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use function strtolower;

class LandConfigForm
{
    private array $landConfigCache;

    public function __construct()
    {
        $this->landConfigCache = [];
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    if ($data === 0) {
                        $form = new LandManagerForm();
                        $form->execute($player);
                    }
                    elseif ($data === 1 and !isset($this->landConfigCache[strtolower($player->getName())])) {
                        $this->landConfigCache[strtolower($player->getName())] = array(
                            "x" => (int)$player->getPosition()->getX(),
                            "z" => (int)$player->getPosition()->getZ()
                        );
                        $player->sendMessage("§a[システム] 開始X座標を" . (int)$player->getPosition()->getX() . "\n開始Z座標を" . (int)$player->getPosition()->getZ() . "に設定しました");
                    }
                    elseif ($data === 1 and isset($this->landConfigCache[strtolower($player->getName())])) {

                    }
                    elseif ($data === 2 and isset($this->landConfigCache[strtolower($player->getName())])) {
                        unset($this->landConfigCache[strtolower($player->getName())]);
                        $player->sendMessage("§a[システム] 開始座標をリセットしました");
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });
            if (!isset($this->landConfigCache[strtolower($player->getName())])) {
                $form->addButton("開始座標の設定");
            }
            else {
                $form->addButton("終了座標の設定");
                $form->addButton("開始座標のリセット");
            }

        }
        catch (Error | Exception $error) {

        }
    }
}