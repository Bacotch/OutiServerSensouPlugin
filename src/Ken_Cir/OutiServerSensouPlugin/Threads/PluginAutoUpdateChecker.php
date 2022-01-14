<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use function json_decode;
use function file_put_contents;
use function count;
use function extension_loaded;
use function register_shutdown_function;
use function unlink;
use function rename;
use function pcntl_exec;
use const DIRECTORY_SEPARATOR;

/**
 * このプラグインのアップデートを確認するAsyncTask
 */
final class PluginAutoUpdateChecker extends AsyncTask
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        $response = Internet::getURL("https://kencir.github.io/OutiServerSensouPlugin/version/");
        if ($response !== null) {
            $response = json_decode($response->getBody(), true);
            $this->setResult($response);
        }
        else {
            $this->setResult(null);
        }
    }

    public function onCompletion(): void
    {
        $result = $this->getResult();
        if ($result !== null and Main::getInstance()->getPluginData()->get("pluginLastUpdateVersion", "") !== $result["version"] and extension_loaded('pcntl') and DIRECTORY_SEPARATOR === '/') {
            Main::getInstance()->getLogger()->alert("おうち鯖プラグインの新バージョンがあります！新しいバージョン: {$result["version"]}");

            // TODO: 404ERRORの修正？
            $pluginPhar = Internet::getURL($result["downloadURL"]);
            var_dump($pluginPhar);
            file_put_contents(Server::getInstance()->getPluginPath() . "outiserverpmmpplugin1.phar", $pluginPhar->getBody());
            Main::getInstance()->getPluginData()->set("pluginLastUpdateVersion", $result["version"]);

            // シャットダウン関数を登録
            register_shutdown_function(function() {
                @unlink(Server::getInstance()->getPluginPath() ."outiserverpmmpplugin.phar");
                rename(Server::getInstance()->getPluginPath() . "outiserverpmmpplugin1.phar",Server::getInstance()->getPluginPath() . "outiserverpmmpplugin.phar");
                pcntl_exec("./start.sh");
            });

            if (count(Server::getInstance()->getOnlinePlayers()) < 1) {
                Main::getInstance()->getLogger()->alert("アップデートの準備が整いました！サーバーを再起動しています...");
                Server::getInstance()->shutdown();
            }
           else {
               Main::getInstance()->getLogger()->alert("アップデートの準備が整いました！アップデートを待機しています...");
               Server::getInstance()->broadcastMessage("§a[システム] §e[警告] §fサーバーアップデートの準備が整いました！あと10分でサーバーは再起動されます");
               Main::getInstance()->getScheduler()->scheduleRepeatingTask(new AutoUpdateWait(), 20);
           }
        }
    }
}