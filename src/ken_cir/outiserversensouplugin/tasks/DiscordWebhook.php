<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use InvalidArgumentException;
use pocketmine\scheduler\AsyncTask;
use function curl_init;
use function curl_setopt;
use function curl_exec;
use function curl_close;
use function json_encode;
use const CURLOPT_URL;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYPEER;

/**
 * DiscordにWebhook経由で何か送る用の非同期TASK
 */
final class DiscordWebhook extends AsyncTask
{
    /**
     * WebhookのURL
     *
     * @var string
     */
    private string $webhookURL;

    private string $content;

    public function __construct(string $webhookURL, string $content)
    {
        if ($webhookURL === "") throw new InvalidArgumentException("webhookURLを空にすることはできません");
        if ($content === "") throw new InvalidArgumentException("contentを空にすることはできません");
        $this->webhookURL = $webhookURL;
        $this->content = $content;
    }

    public function onRun(): void
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->webhookURL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(
            array(
                'content' => $this->content
            )
        ));
        curl_exec($curl);
        curl_close($curl);
    }
}