<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\schedulemessagedata;

use InvalidArgumentException;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\plugin\PluginOwned;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use function array_values;
use function count;

/**
 * 定期メッセージデータマネージャー
 */
class ScheduleMessageDataManager
{
    private DataConnector $connector;

    /**
     * インスタンス
     *
     * @var ScheduleMessageDataManager $this
     */
    private static self $instance;

    /**
     * @var ScheduleMessageData[]
     */
    private array $scheduleMessageDatas;

    /**
     * 管理用ID
     *
     * @var int
     */
    private int $seq;

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->scheduleMessageDatas = [];

        $this->connector->executeSelect("outiserver.schedulemessages.seq",
            [],
            function (array $row) {
                if (count($row) < 1) {
                    $this->seq = 0;
                    return;
                }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
        $this->connector->executeSelect("outiserver.schedulemessages.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->scheduleMessageDatas[$data["id"]] = new ScheduleMessageData($data["id"], $data["content"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
    }

    /**
     * @return ScheduleMessageDataManager
     */
    public static function getInstance(): ScheduleMessageDataManager
    {
        return self::$instance;
    }

    /**
     * 定期メッセージをIDで取得する
     *
     * @param int $id
     * @return ScheduleMessageData|null
     */
    public function get(int $id): ?ScheduleMessageData
    {
        if (!isset($this->scheduleMessageDatas[$id])) return null;
        return $this->scheduleMessageDatas[$id];
    }

    /**
     * 定期メッセージを配列で全取得
     *
     * @return ScheduleMessageData[]
     */
    public function getAll(): array
    {
        return array_values($this->scheduleMessageDatas);
    }

    /**
     * 定期メッセージを作成
     *
     * @param string $content
     * @return void
     */
    public function create(string $content): void
    {
        $this->connector->executeInsert("outiserver.schedulemessages.create",
            [
                "content" => $content,
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        $this->seq++;
        $this->scheduleMessageDatas[$this->seq] = new ScheduleMessageData($this->seq, $content);
    }

    /**
     * 定期メッセージを削除
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->connector->executeGeneric("outiserver.schedulemessages.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->scheduleMessageDatas[$id]);
    }
}