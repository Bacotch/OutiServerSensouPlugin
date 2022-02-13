<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\maildata;

use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;

/**
 * メールデータ
 */
class MailData
{
    /**
     * @var int
     * メール識別用ID
     */
    private int $id;

    /**
     * @var string
     * メール送信先プレイヤーXUID
     */
    private string $sendto_xuid;

    /**
     * @var string
     * メールタイトル
     */
    private string $title;

    /**
     * @var string
     * メール内容
     */
    private string $content;

    /**
     * @var string
     * メール送信プレイヤーXUID
     */
    private string $author_xuid;

    /**
     * @var string
     * メール送信日時
     */
    private string $date;

    /**
     * @var int
     * メールが未読であるか
     */
    private int $read;

    public function __construct(int $id, string $sendto_xuid, string $title, string $content, string $author_xuid, string $date, int $read)
    {
        $this->id = $id;
        $this->sendto_xuid = $sendto_xuid;
        $this->title = $title;
        $this->content = $content;
        $this->author_xuid = $author_xuid;
        $this->date = $date;
        $this->read = $read;
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
    public function getSendtoXuid(): string
    {
        return $this->sendto_xuid;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getAuthorXuid(): string
    {
        return $this->author_xuid;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @return bool
     */
    public function isRead(): bool
    {
        return (bool)$this->read;
    }

    /**
     * @param bool $read
     */
    public function setRead(bool $read): void
    {
        $this->read = (int)$read;
        Main::getInstance()->getDatabase()->executeChange(
            "outiserver.mails.update",
            [
                "read" => $this->read,
                "id" => $this->id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }
}
