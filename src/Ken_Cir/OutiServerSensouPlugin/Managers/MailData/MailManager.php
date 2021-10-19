<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\MailData;

final class MailManager
{
    /**
     * @var MailData[]
     */
    private array $mail_datas;

    public function __construct(array $mails)
    {
        if (count($mails) < 1) {
            $this->mail_datas = [];
            return;
        }
        foreach ($mails as $mail) {
            $this->mail_datas[$mail["title"]] = new MailData($mail["title"], $mail["content"], $mail["author"], $mail["date"], $mail["read"]);
        }
    }

    /**
     * @return MailData[]
     */
    public function getAll(): array
    {
        return $this->mail_datas;
    }

    /**
     * @return array[]
     */
    public function getMailDatas(): array
    {
        $mails = [];
        foreach ($this->mail_datas as $mailData) {
            $mails[] = $mailData->toArray();
        }
        return $mails;
    }

    /**
     * @param string $title
     * @return bool|MailData
     * メールデータを取得する
     */
    public function get(string $title): bool|MailData
    {
        if (!isset($this->mail_datas[$title])) return false;
        return $this->mail_datas[$title];
    }

    /**
     * @param string $title
     * @param string $content
     * @param string $author
     * @param string $date
     */
    public function create(string $title, string $content, string $author, string $date)
    {
        $this->mail_datas[$title] = new MailData($title, $content, $author, $date, false);
    }

    /**
     * @param string $title
     * メールを削除する
     */
    public function delete(string $title)
    {
        unset($this->mail_datas[$title]);
    }

    /**
     * @return int
     * 未読のメール数を返す
     */
    public function unReadCount(): int
    {
        $count = 0;
        foreach ($this->mail_datas as $mailData) {
            if (!$mailData->isRead()) $count++;
        }

        return $count;
    }
}