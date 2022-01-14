<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\ScheduleMessageData;

use InvalidArgumentException;
use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;

/**
 * 定期メッセージデータ
 */
class ScheduleMessageData
{
    /**
     * 管理用ID
     *
     * @var int
     */
    private int $id;

    /**
     * メッセージ内容
     *
     * @var string
     */
    private string $content;

    public function __construct(int $id, string $content)
    {
        $this->id = $id;
        $this->content = $content;
    }

    /**
     * データ保存
     */
    public function update()
    {
        try {
            Main::getInstance()->getDatabase()->executeChange("schedulemessages.update",
                [
                    "content" => $this->content,
                    "id" => $this->id
                ],
                null,
                function (SqlError $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            );
        }
        catch (InvalidArgumentException $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->update();
    }
}