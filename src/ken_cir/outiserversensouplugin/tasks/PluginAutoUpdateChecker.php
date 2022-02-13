<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use ken_cir\outiserversensouplugin\Main;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use function count;
use function extension_loaded;
use function file_put_contents;
use function json_decode;
use function pcntl_exec;
use function register_shutdown_function;
use function rename;
use function unlink;
use const DIRECTORY_SEPARATOR;

/**
 * このプラグインのアップデートを確認するAsyncTask
 */
class PluginAutoUpdateChecker extends AsyncTask
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        $response = Internet::getURL("https://kencir.github.io/outiserversensouplugin/version/");
        if ($response !== null) {
            $response = json_decode($response->getBody(), true);
            $this->setResult($response);
        } else {
            $this->setResult(null);
        }
    }

    public function onCompletion(): void
    {
        // 今はリポジトリがプライベートになっているのでここで処理を中断させています
        return;

        $result = $this->getResult();
        if ($result !== null and Main::getInstance()->getPluginData()->get("pluginLastUpdateVersion", "") !== $result["version"] and extension_loaded('pcntl') and DIRECTORY_SEPARATOR === '/') {
            Main::getInstance()->getLogger()->alert("おうち鯖プラグインの新バージョンがあります！新しいバージョン: {$result["version"]}");

            $pluginPhar = Internet::getURL($result["downloadURL"]);
            file_put_contents(Server::getInstance()->getPluginPath() . "outiserverpmmpplugin1.phar", $pluginPhar->getBody());
            Main::getInstance()->getPluginData()->set("pluginLastUpdateVersion", $result["version"]);

            // シャットダウン関数を登録
            register_shutdown_function(function () {
                @unlink(Server::getInstance()->getPluginPath() . "outiserverpmmpplugin.phar");
                rename(Server::getInstance()->getPluginPath() . "outiserverpmmpplugin1.phar", Server::getInstance()->getPluginPath() . "outiserverpmmpplugin.phar");
                pcntl_exec("./start.sh");
            });

            if (count(Server::getInstance()->getOnlinePlayers()) < 1) {
                Main::getInstance()->getLogger()->alert("アップデートの準備が整いました！サーバーを再起動しています...");
                Server::getInstance()->shutdown();
            } else {
                Main::getInstance()->getLogger()->alert("アップデートの準備が整いました！アップデートを待機しています...");
                Server::getInstance()->broadcastMessage("§a[システム] §e[警告] §fサーバーアップデートの準備が整いました！あと10分でサーバーは再起動されます");
                Main::getInstance()->getScheduler()->scheduleRepeatingTask(new AutoUpdateWait(), 20);
            }
        }
    }
}