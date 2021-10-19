<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\MailData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;

final class MailData
{

    /**
     * @var string
     * メールタイトル
     */
    private string $title;
    private string $content;
    private string $author;
    private string $date;
    private bool $read;

    public function __construct(string $title, string $content, string $author, string $date, bool $read)
    {
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->date = $date;
        $this->read = $read;
    }


    public function toArray()
    {
        return array(
            "title" => $this->title,
            "content" => $this->content,
            "author" => $this->author,
            "date" => $this->date,
            "read" => $this->read
        );
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
        return $this->read;
    }

    /**
     * @param bool $read
     */
    public function setRead(bool $read): void
    {
        $this->read = $read;
    }
}