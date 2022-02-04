<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\schedulemessagedata;

use InvalidArgumentException;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\libs\poggit\libasynql\SqlError;

/**
 * 定期メッセージデータ
 */
final class ScheduleMessageData
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
    private function update()
    {
        try {
            Main::getInstance()->getDatabase()->executeChange("outiserver.schedulemessages.update",
                [
                    "content" => $this->content,
                    "id" => $this->id
                ],
                null,
                function (SqlError $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error);
                }
            );
        }
        catch (InvalidArgumentException $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
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