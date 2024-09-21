<?php /** @noinspection ForgottenDebugOutputInspection */

namespace app\test;

require __DIR__ . '/../../vendor/autoload.php';

use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use PHPUnit\Framework\TestCase;
use think\App;

class TelegramBotTest extends TestCase
{
    private object $telegram;

    public function __construct(string $name)
    {
        parent::__construct($name);
        (new App())->initialize();
        try {
            $this->telegram = new Telegram(env('TELEGRAM_TOKEN'), env('TELEGRAM_BOT_NAME'));
        } catch (TelegramException $e) {
            dump($e);
        }
    }

    public static function chatIdAndMessage(): array
    {
        return [
            [1118481829, 'hello'],
            [1118481829, 'world'],
        ];
    }

    /**
     * @dataProvider chatIdAndMessage
     */
    public function testSendMessage($chat_id, $message): void
    {
        if ($chat_id !== '' && $message !== '') {
            $data = [
                'chat_id' => $chat_id,
                'text'    => $message,
            ];

            try {
                $result = Request::sendMessage($data);
                if ($result->isOk()) {
                    dump($result);
                    dump('Message sent successfully to: ' . $chat_id);
                } else {
                    dump('Sorry message not sent to: ' . $chat_id);
                }
            } catch (TelegramException $e) {
                dump($e);
            }
        }
    }

    public function testGetUpdate(): void
    {
        try {
            $this->telegram->enableMySql([
                'host'     => env('DB_HOST'),
                'port'     => env('DB_PORT'),
                'user'     => env('DB_USER'),
                'password' => env('DB_PASS'),
                'database' => 'tg',
            ]);
            $result = $this->telegram->handleGetUpdates()->getRawData()['result'];
            dump(array_map(static function ($object) {
                /** @var Update  $object*/
                return $object->getMessage()->getText();
            }, $result));
        } catch (TelegramException $e) {
            dump($e);
        }
    }
}
