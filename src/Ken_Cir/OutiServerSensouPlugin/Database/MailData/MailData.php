<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\MailData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;
use function strtolower;

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
     * メール送信相手
     */
    private string $name;

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
     * メール送信者
     */
    private string $author;

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

    public function __construct(int $id, string $name, string $title, string $content, string $author, string $date, int $read)
    {
        $this->id = $id;
        $this->name = strtolower($name);
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
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
    public function getName(): string
    {
        return $this->name;
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
    public function getAuthor(): string
    {
        return $this->author;
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
        try {
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
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }
}
