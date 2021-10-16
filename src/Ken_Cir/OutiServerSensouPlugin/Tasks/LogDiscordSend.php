<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Tasks;

use DateTime;
use DateTimeZone;
use Error;
use Exception;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Config;

/**
 * ログをDiscordに送信するタスク
 */
class LogDiscordSend extends AsyncTask
{
    // ---ログの種類
    /**
     * @var int
     * サーバー関係ログ
     */
    const SERVER = 0;

    /**
     * @var int
     * エラーログ
     */
    const ERROR = 1;

    /**
     * @var int
     * プラグインログ
     */
    const PLUGIN = 2;

    /**
     * @var int
     * Playerログ
     */
    const PLAYER = 3;

    /**
     * @var Config
     * コンフィグインスタンス
     */
    private Config $config;

    /**
     * @var string
     * ログメッセージ
     */
    private string $message;

    /**
     * @var int
     * ログ種類
     * 詳細は定数参照
     */
    private int $type;

    /**
     * @param Config $config
     * @param string $message
     * @param int $type
     * 初期化
     */
    public function __construct(Config $config, string $message, int $type)
    {
        $this->config = $config;
        $this->message = $message;
        $this->type = $type;
    }

    /**
     * 実行
     */
    public function onRun()
    {
        try {
            // typeの値でurlが変わるのでここでmatchさせます
            $webhook = match ($this->type) {
                self::SERVER => $this->config->get("Discord_Server_Webhook", ""),
                self::ERROR => $this->config->get("Discord_Error_Webhook", ""),
                self::PLUGIN => $this->config->get("Discord_Plugin_Webhook", ""),
                self::PLAYER => $this->config->get("Discord_Player_Webhook", ""),
                default => ""
            };

            if ($webhook === "" or $this->message === "") return;

            try {
                $time = new DateTime('NOW', new DateTimeZone("Asia/Tokyo"));
            } catch (Exception $error) {
                return;
            }

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $webhook);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(
                array(
                    'content' => "```[{$time->format('Y-m-d H:i:sP')}]: $this->message```"
                )
            ));
            curl_exec($curl);
        } catch (Error | Exception $error) {
            echo "Discordにログを送信できませんでした\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}" . PHP_EOL;
        }
    }
}