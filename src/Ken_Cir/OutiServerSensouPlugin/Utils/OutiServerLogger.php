<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Utils;

use DateTime;
use DateTimeZone;
use Error;
use Exception;
use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Threads\DiscordWebhook;
use pocketmine\Player\player;
use pocketmine\Server;

/**
 * おうち鯖プラグインログ関係クラス
 */
class OutiServerLogger
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

            try {
                $time = new DateTime('NOW', new DateTimeZone("Asia/Tokyo"));
                // もしPlayerインスタンスが引数に指定されていて予期せぬエラーだったら
                if ($player instanceof Player and $emergency) {
                    $player->sendMessage("§a[システム] 予期せぬエラーが処理中に発生しました、開発者に連絡してください\n§eーーー↓以下開発者確認用↓ーーー\n§cPlayer: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}\nTrace: {$error->getTraceAsString()}");
                    Server::getInstance()->getAsyncPool()->submitTask(
                        new DiscordWebhook(
                            Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", ""),
                            "予期せぬエラーが発生しました```Player: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}\nTrace: {$error->getTraceAsString()}```"
                        )
                    );
                }
                // もししPlayerインスタンスが引数に指定されていたら
                elseif ($player instanceof Player) {
                    $player->sendMessage("§a[システム] 処理中にエラー発生しました、現在行っていた処理は中断されます\n§eーーー↓以下開発者確認用↓ーーー\n§cPlayer: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}\nTrace: {$error->getTraceAsString()}");
                    Server::getInstance()->getAsyncPool()->submitTask(
                        new DiscordWebhook(
                            Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", ""),
                            "処理中にエラー発生しました```Player: {$player->getName()}(XUID: {$player->getXuid()})\nTime: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}\nTrace: {$error->getTraceAsString()}```"
                        )
                    );
                }
                elseif ($emergency) {
                    Server::getInstance()->getAsyncPool()->submitTask(
                        new DiscordWebhook(
                            Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", ""),
                            "予期せぬエラーが発生しました```Time: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}\nTrace: {$error->getTraceAsString()}```"
                        )
                    );
                }
                else {
                    Server::getInstance()->getAsyncPool()->submitTask(
                        new DiscordWebhook(
                            Main::getInstance()->getPluginConfig()->get("Discord_Error_Webhook", ""),
                            "エラーが発生しました```Time: {$time->format('Y-m-d H:i:sP')}\nFile: {$error->getFile()}\nLine: {$error->getLine()}\nMessage: {$error->getMessage()}\nTrace: {$error->getTraceAsString()}```"
                        )
                    );
                }
            }
            catch (InvalidArgumentException $exception) {
                Main::getInstance()->getLogger()->error("エラーが発生しました\nFile: {$exception->getFile()}\nLine: {$exception->getLine()}\nMessage: {$exception->getMessage()}\nTrace: {$exception->getTraceAsString()}");
            }
        }
        catch (Error | Exception $error_) {
            Main::getInstance()->getLogger()->emergency("予期せぬエラーが発生しました、開発者に連絡してください\nFile: {$error_->getFile()}\nLine: {$error_->getLine()}\nMessage: {$error_->getMessage()}\nTrace: {$error_->getTraceAsString()}");
        }
    }
}