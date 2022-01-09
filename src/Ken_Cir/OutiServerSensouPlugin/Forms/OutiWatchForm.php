<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\EventListener;
use Ken_Cir\OutiServerSensouPlugin\Forms\Admin\AdminForm;
use Ken_Cir\OutiServerSensouPlugin\Forms\Faction\FactionForm;
use Ken_Cir\OutiServerSensouPlugin\Forms\Mail\MailForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\player\Player;
use pocketmine\Server;
use Vecnavium\FormsUI\SimpleForm;
use function strtolower;

/**
 * おうちウォッチ
 */
class OutiWatchForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * フォーム実行
     */
    public function execute(Player $player, ?EventListener $eventListener = null)
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($eventListener) {
                try {
                    if ($eventListener instanceof EventListener) {
                        $eventListener->unsetCheck($player->getName());
                    }

                    if ($data === null) return true;
                    elseif ($data === 1) {
                        $form = new FactionForm();
                        $form->execute($player);
                    } elseif ($data === 2) {
                        $form = new MailForm();
                        $form->execute($player);
                    } elseif ($data === 3) {
                        $form = new ReportForm();
                        $form->execute($player);
                    } elseif ($data === 4) {
                        $form = new RequestForm();
                        $form->execute($player);
                    }
                    elseif ($data === 5 and Server::getInstance()->isOp($player->getName())) {
                        $form = new AdminForm();
                        $form->execute($player);
                    }
                } catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });

            $form->setTitle("おうちウォッチ");
            $form->addButton("§c閉じる");
            $form->addButton("§d派閥");
            $form->addButton("§eメール");
            $form->addButton("§4レポート");
            $form->addButton("§6要望");
            if (Server::getInstance()->isOp($player->getName())) {
                $form->addButton("管理者");
            }
            $form->addButton("テスト", 0, "textures/items/facebook");
            $player->sendForm($form);
        }
        catch (Error|Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, $player);
        }
    }
}