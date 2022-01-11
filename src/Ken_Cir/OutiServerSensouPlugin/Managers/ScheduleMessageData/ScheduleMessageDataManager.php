<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\ScheduleMessageData;

use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;
use function count;
use function array_values;

/**
 * 定期メッセージデータマネージャー
 */
class ScheduleMessageDataManager
{
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

    public function __construct()
    {
        self::$instance = $this;
        $this->scheduleMessageDatas = [];
        Main::getInstance()->getDatabase()->executeSelect("schedulemessages.seq",
            [],
            function (array $row) {
                if (count($row) < 1)  {
                    $this->seq = 0;
                    return;
                }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
        Main::getInstance()->getDatabase()->waitAll();
        Main::getInstance()->getDatabase()->executeSelect("schedulemessages.load",
            [],
            function (array $row) {
                try {
                    foreach ($row as $data) {
                        $this->scheduleMessageDatas[$data["id"]] = new ScheduleMessageData($data["id"], $data["content"]);
                    }
                }
                catch (InvalidArgumentException $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
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
        Main::getInstance()->getDatabase()->executeInsert("schedulemessages.create",
            [
                "content" => $content,
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
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
        Main::getInstance()->getDatabase()->executeInsert("schedulemessages.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->scheduleMessageDatas[$id]);
    }
}