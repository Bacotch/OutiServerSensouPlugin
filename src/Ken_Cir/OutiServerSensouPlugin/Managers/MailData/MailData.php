<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\MailData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;

class MailData
{
    private int $id;
    private string $name;
    /**
     * @var string
     * メールタイトル
     */
    private string $title;
    private string $content;
    private string $author;
    private string $date;
    private int $read;

    public function __construct(int $id, string $name, string $title, string $content, string $author, string $date, int $read)
    {
        $this->id = $id;
        $this->name = $name;
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
            Main::getInstance()->getDatabase()->executeChange("mails.update",
                [
                    "read" => $this->read,
                    "id" => $this->id
                ],
                null,
                function (SqlError $error) {
                    Main::getInstance()->getPluginLogger()->error($error);
                }
            );
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}