<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Mail;

use DateTime;
use Error;
use Exception;
use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Database\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Threads\ReturnForm;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\Server;
use Vecnavium\FormsUI\CustomForm;

/**
 * メール作成フォーム
 */
class CreateMailForm
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
                    elseif (!isset($data[0]) or !isset($data[1]) or !isset($data[2])) return true;
                    $author = $player->getName();
                    $time = new DateTime('now');
                    // 送信者名義を「運営」に
                    if (isset($data[4])) {
                        $author = "運営";
                    }
                    // 全員に送信する
                    if (isset($data[3])) {
                        foreach (PlayerDataManager::getInstance()->getPlayerDatas() as $playerData) {
                            MailManager::getInstance()->create($playerData->getName(), $data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                        }
                        $player->sendMessage("§a[システム] プレイヤー全員にメールを送信しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                        return true;
                    }

                    try {
                        MailManager::getInstance()->create($data[2], $data[0], $data[1], $author, $time->format("Y年m月d日 H時i分"));
                        $player->sendMessage("§a[システム] プレイヤー $data[2] にメールを送信しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 20);
                    }
                    catch (InvalidArgumentException $exception) {
                        Main::getInstance()->getOutiServerLogger()->error($exception, $player);
                    }
                }
                catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("§aメール送信フォーム");
            $form->addInput("§cメールタイトル", "title", "");
            $form->addInput("§d内容", "content", "");
            $form->addInput("§6送信相手", "send_to", "");
            if (Main::getInstance()->getServer()->isOp($player->getName())) {
                $form->addToggle("§3[運営専用] プレイヤーにメールを送信する");
                $form->addToggle("§3[運営専用] 送信者名義を「運営」にして送信する");
            }
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
