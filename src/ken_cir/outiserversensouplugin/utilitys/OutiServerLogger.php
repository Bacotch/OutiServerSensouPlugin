<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\utilitys;

use DateTime;
use DateTimeZone;
use Error;
use Exception;
use InvalidArgumentException;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\threads\DiscordWebhook;
use pocketmine\Player\player;
use pocketmine\Server;

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

            Main::getInstance()->getLogger()->error($errmsg);
            Server::getInstance()->getAsyncPool()->submitTask(
                new DiscordWebhook(
                    Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", ""),
                    $errmsg
                )
            );
        }
        catch (Error | Exception $error_) {
            Main::getInstance()->getLogger()->emergency("予期せぬエラーが発生しました、開発者に連絡してください\nFile: {$error_->getFile()}\nLine: {$error_->getLine()}\nMessage: {$error_->getMessage()}");
        }
    }
}