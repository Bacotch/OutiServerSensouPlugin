<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Threads;

use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use ZipArchive;
use function is_file;
use function opendir;
use function readdir;
use function closedir;
use function date;
use function str_ends_with;

class Backup extends AsyncTask
{
    private string $serverDataFloder;
    private string $pluginDataFloder;

    public function __construct()
    {
        $this->serverDataFloder = Server::getInstance()->getDataPath();
        $this->pluginDataFloder = Main::getInstance()->getDataFolder();
        Main::getInstance()->getLogger()->info("バックアップを作成します...");
    }

    public function onRun(): void
    {
        $zip = new ZipArchive;
        if($zip->open($this->pluginDataFloder . "backups/" . date("Y-m-d-H-i-s") . ".backup.zip",ZipArchive::CREATE)=== TRUE) {
            $this->zipSub($zip, $this->serverDataFloder);
            $zip->close();
        }
    }

    public function onCompletion(): void
    {
        Main::getInstance()->getLogger()->info("バックアップの作成が完了しました");
    }

    /**
     * @param ZipArchive $zip
     * playersのバックアップ
     */
    private function zipSub(ZipArchive $zip, string $path, string $parentPath = '')
    {
        $dir = opendir($path);
        while (($entry = readdir($dir)) !== false) {
            if ($entry == '.' || $entry == '..' || $entry === '.DS_Store' || str_ends_with($entry, "backup") || str_ends_with($entry, "backup.zip")) continue;
            else {
                $localPath = "$parentPath$entry";
                $fullpath = "$path/$entry";
                if (is_file($fullpath)) {
                    $zip->addFile($fullpath, "$localPath");
                }
                elseif (is_dir($fullpath)) {
                    $this->zipSub($zip, $fullpath, $localPath.'/');
                }
            }
        }
        closedir($dir);
    }


}