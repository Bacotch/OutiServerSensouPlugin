<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\utilitys;

use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use DateTime;
use DateTimeZone;
use Error;
use Exception;
use InvalidArgumentException;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\Player\player;
use function str_replace;

/**
 * おうち鯖プラグインログ関係クラス
 */
final class OutiServerLogger
{
    public function __construct()
    {
    }

    /**
     * エラー出力
     *
     * @param $error
     * @param bool $emergency
     * @param Player|null $player
     */
    public function error($error, bool|Player $emergency = false, ?Player $player = null)
    {
        try {
            if ($emergency instanceof Player) {
                $player = $emergency;
                $emergency = false;
            }

            $time = new DateTime('NOW', new DateTimeZone("Asia/Tokyo"));

            if ($player instanceof Player) {
                if ($emergency) {
                    $errmsgPlayer = "§a[システム] 予期せぬエラーが処理中に発生しました、開発者に連絡してください\n§eーーー以下開発者確認用ーーー\n§cPlayer: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}";
                    $errmsg = "予期せぬエラーが発生しました```Player: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}```";
                }
                else {
                    $errmsgPlayer = "§a[システム] 処理中にエラー発生しました、現在行っていた処理は中断されます\n§eーーー以下開発者確認用ーーー\n§cPlayer: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}";
                    $errmsg = "処理中にエラー発生しました```Player: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}```";
                }

                $player->sendMessage($errmsgPlayer);
            }
            elseif ($emergency) {
                $errmsg = "予期せぬエラーが発生しました```Time: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}```";
            }
            else {
                $errmsg = "エラーが発生しました```Time: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}```";
            }

            Main::getInstance()->getLogger()->error(str_replace("```", "", $errmsg));

            if (($webhookURL = (string)Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", "")) !== "") {
                $webhook = new Webhook($webhookURL);
                $time = new DateTime('NOW', new DateTimeZone("Asia/Tokyo"));
                $msg = new Message();
                $msg->setContent("[{$time->format('Y-m-d H:i:sP')}] $errmsg");
                $webhook->send($msg);
            }
        }
        catch (Error | Exception $error_) {
            Main::getInstance()->getLogger()->emergency("予期せぬエラーが発生しました、開発者に連絡してください\nFile: {$error_->getFile()}\nLine: {$error_->getLine()}\nMessage: {$error_->getMessage()}");
        }
    }

    public function debug(string $message, ?Player $player = null): void
    {
        try {
            if ($message === "") throw new InvalidArgumentException("\$message a cannot be an empty string");

            Main::getInstance()->getLogger()->debug($message);

            if ($player instanceof Player) {
                $player->sendMessage("[DEBUG] $message");
            }

            if (($webhookURL = (string)Main::getInstance()->getPluginConfig()->get("Discord_Plugin_Webhook", "")) !== "") {
                $webhook = new Webhook($webhookURL);
                $time = new DateTime('NOW', new DateTimeZone("Asia/Tokyo"));
                $msg = new Message();
                $msg->setContent("```[{$time->format('Y-m-d H:i:sP')}] $message```");
                $webhook->send($msg);
            }
        }
        catch (Error | Exception $error_) {
            Main::getInstance()->getLogger()->emergency("予期せぬエラーが発生しました、開発者に連絡してください\nFile: {$error_->getFile()}\nLine: {$error_->getLine()}\nMessage: {$error_->getMessage()}");
        }
    }
}