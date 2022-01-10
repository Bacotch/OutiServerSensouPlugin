<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;

/**
 * 要望フォーム
 */
class RequestForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * フォーム実行
     */
    public function execute(Player $player)
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif (!isset($data[0])) return true;
                    $player->sendMessage("§a[システム] 要望を送信しました");
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });

            $form->setTitle("要望フォーム");
            $form->addInput("§d内容", "content");
            $form->addLabel("§e要望内容に対する返信は内部メールで行います");
            $player->sendForm($form);
        }
        catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}