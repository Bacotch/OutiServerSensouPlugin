<?php

declare(strict_types=1);

namespace OutiServerPlugin\Tasks;

use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Member;
use pocketmine\Thread;
use pocketmine\utils\TextFormat;
use React\EventLoop\Factory;
use Threaded;

/**
 * DiscordBot用のスレッド
 */
class DiscordBot extends Thread
{
    /**
     * @var bool
     * このスレッドが開始しているかどうか
     */
    public bool $started;

    /**
     * @var bool
     * このスレッドを終了させるかどうか
     */
    private bool $stoped;

    /**
     * @var string
     * BotのTOKEN
     */
    private string $token;

    /**
     * @var string
     * vectorのディレクトリ
     */
    private string $vector_dir;

    /**
     * @var string
     * ギルドID
     */
    private string $guild_id;

    /**
     * @var string
     * コンソール用チャンネルID
     */
    private string $console_channel_id;

    /**
     * @var string
     * チャット用チャンネルID
     */
    private string $chat_channel_id;

    /**
     * @var Threaded
     * Discordコンソールキュー
     */
    protected Threaded $DiscordConsole_Queue;

    /**
     * @var Threaded
     * Minecraftコンソールキュー
     */
    protected Threaded $MinecraftConsole_Queue;

    /**
     * @var Threaded
     * Discordチャットキュー
     */
    protected Threaded $DiscordChat_Queue;

    /**
     * @var Threaded
     * Minecraftチャットキュー
     */
    protected Threaded $MinecraftChat_Queue;

    public function __construct(string $token, string $vector_dir, string $guild_id, string $console_channel_id, string $chat_channel_id)
    {
        $this->started = false;
        $this->stoped = false;
        $this->token = $token;
        $this->vector_dir = $vector_dir;
        $this->guild_id = $guild_id;
        $this->console_channel_id = $console_channel_id;
        $this->chat_channel_id = $chat_channel_id;
        $this->DiscordConsole_Queue = new Threaded;
        $this->MinecraftConsole_Queue = new Threaded;
        $this->DiscordChat_Queue = new Threaded;
        $this->MinecraftChat_Queue = new Threaded;

        $this->start(PTHREADS_INHERIT_CONSTANTS);
    }

    /**
     * 実行
     */
    public function run()
    {
        $this->registerClassLoader();

        include $this->vector_dir . "vendor/autoload.php";

        $loop = Factory::create();

        try {
            $discord = new Discord([
                "token" => $this->token,
                "loop" => $loop,
            ]);
        } catch (IntentException $error) {
            echo "エラーが発生しました\nファイル: {$error->getFile()}\n行: {$error->getLine()}\n{$error->getMessage()}" . PHP_EOL;
            echo "DiscordPHP Botにログインできません" . PHP_EOL;
            unset($this->token);
            $this->isKilled = true;
            return;
        }

        unset($this->token);

        $loop->addPeriodicTimer(1, function () use ($discord) {
            if ($this->isKilled) {
                $discord->close();
                $discord->loop->stop();
                $this->started = false;
            }
        });

        $loop->addPeriodicTimer(1, function () use ($discord) {
            $this->task($discord);
        });

        $discord->on('ready', function (Discord $discord) {
            $this->started = true;
            echo "Bot is ready." . PHP_EOL;
            $embed = new Embed($discord);
            $embed->setTitle("テスト");
            $discord->getChannel("897421937444282398")->sendEmbed(
                $embed
            );

            $discord->on('message', function (Message $message) use ($discord) {
                if ($message->author instanceof Member ? $message->author->user->bot : $message->author->bot or $message->type !== Message::TYPE_NORMAL or $message->channel->type !== Channel::TYPE_TEXT or $message->content === "") return;
                // コンソールチャンネルからのメッセージだった場合は
                if ($message->channel_id === $this->console_channel_id) {
                    $this->DiscordConsole_Queue[] = serialize($message->content);
                } // チャットチャンネルからのメッセージだった場合は
                elseif ($message->channel_id === $this->chat_channel_id) {
                    $this->DiscordChat_Queue[] = serialize([
                        "username" => $message->author->username,
                        "content" => $message->content
                    ]);
                }
            });
        });

        $discord->run();
    }

    /**
     * @param bool $emergency
     * スレッドを停止する
     */
    public function shutdown(?bool $emergency = false)
    {
        if ($emergency) {
            $this->stoped = true;
            $this->isKilled = true;
        } else $this->stoped = true;
    }

    /**
     * @param string $message
     * DiscordConsoleにメッセージを送信する
     */
    public function sendConsoleMessage(string $message)
    {
        if ($this->stoped) return;
        $this->MinecraftConsole_Queue[] = serialize($message);
    }

    /**
     * @param string $message
     * DiscordChatにメッセージを送信する
     */
    public function sendChatMessage(string $message)
    {
        if ($this->stoped) return;
        $this->MinecraftChat_Queue[] = serialize($message);
    }

    /**
     * @return array
     * DiscordConsole_Queueのメッセージを配列にして返す
     */
    public function fetchConsoleMessages(): array
    {
        $messages = [];
        while (count($this->DiscordConsole_Queue) > 0) {
            $messages[] = unserialize($this->DiscordConsole_Queue->shift());
        }
        return $messages;
    }

    /**
     * @return array
     * DiscordChat_Queueのメッセージを配列にして返す
     */
    public function fetchChatMessages(): array
    {
        $messages = [];
        while (count($this->DiscordChat_Queue) > 0) {
            $messages[] = unserialize($this->DiscordChat_Queue->shift());
        }
        return $messages;
    }

    /**
     * @param Discord $discord
     * 定期実行Task
     */
    private function task(Discord $discord)
    {
        if (!$this->started) return;

        $guild = $discord->guilds->get('id', $this->guild_id);
        $console_channel = $guild->channels->get('id', $this->console_channel_id);
        $chat_channel = $guild->channels->get('id', $this->chat_channel_id);

        while (count($this->MinecraftConsole_Queue) > 0) {
            $message = unserialize($this->MinecraftConsole_Queue->shift());//
            $message = preg_replace(['/\]0;.*\%/', '/[\x07]/', "/Server thread\//"], '', TextFormat::clean(substr($message, 0, 2000)));//processtile,ANSIコードの削除を実施致します...
            if ($message === "") continue;
            if (strlen($message) <= 2000) {
                $console_channel->sendMessage("```$message```");
            }
        }

        while (count($this->MinecraftChat_Queue) > 0) {
            $message = unserialize($this->MinecraftChat_Queue->shift());
            $message = preg_replace(['/\]0;.*\%/', '/[\x07]/', "/Server thread\//"], '', TextFormat::clean(substr($message, 0, 2000)));//processtile,ANSIコードの削除を実施致します...
            if ($message === "") continue;
            if (strlen($message) <= 2000) {
                $chat_channel->sendMessage($message);
            }
        }

        if ($this->stoped) {
            $this->isKilled = true;
        }
    }
}