<?php

namespace app\test;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;


class MonologTest extends TestCase
{
    private Logger $log;
    private string $log_path = 'E:/test/tmp/test.log';
    private string $message;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->log = new Logger('name');
        $this->log->pushHandler(new StreamHandler($this->log_path, Level::Debug));
    }

    public function getLastContext($path): ?string
    {
        return shell_exec("tail -n 1 $path");
    }

    public function write($message, $level = 'info', $context = []): void
    {
        $this->log->$level($message, $context);
    }

    public function testWrite(): void
    {
        $this->message = 'Adding a new user';
        $this->write($this->message, 'error');
        $this->assertStringContainsString($this->message, $this->getLastContext($this->log_path));
    }

    public function testContext(): void
    {
        $this->message = 'Adding a new user';
        $this->log->info($this->message, ['username' => 'Seldaek']);
        $this->assertStringContainsString($this->message, $this->getLastContext($this->log_path));
        $this->message = 'Adding a new user';
        $this->log->warning($this->message);
        $this->assertStringContainsString($this->message, $this->getLastContext($this->log_path));
    }

    public function testProcessor(): void
    {
        $this->message = 'testProcessor';
        $this->log->pushProcessor(function ($record) {
            $record->extra['dummy'] = $this->message;
            return $record;
        });
        $this->log->info($this->message, ['username' => 'Seldaek']);
        $this->assertStringContainsString($this->message, $this->getLastContext($this->log_path));
    }

    public function testChannel(): void
    {
        $this->message = 'testChannel';
        $stream = new StreamHandler($this->log_path, Level::Debug);
        $firephp = new FirePHPHandler();

        $logger = new Logger('my_logger');
        $logger->pushHandler($stream);
        $logger->pushHandler($firephp);
        $logger->info($this->message);
        $this->assertStringContainsString($this->message, $this->getLastContext($this->log_path));

        $securityLogger = new Logger('security');
        $securityLogger->pushHandler($stream);
        $securityLogger->pushHandler($firephp);
        $securityLogger->info($this->message);
        $this->assertStringContainsString($this->message, $this->getLastContext($this->log_path));
    }

    public function testFormat(): void
    {
        $this->message = 'testFormat';
        $context = [
            'log_id' => '256456585655',
        ];
        $dateFormat = "Y-m-d H:i:sP";
        $output = "[ %datetime% ] [%level_name%] [%channel%] %context.log_id% : %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat, true, true, true);

        $stream = new StreamHandler($this->log_path, Level::Debug);
        $stream->setFormatter($formatter);

        $securityLogger = new Logger('security');
        $securityLogger->pushHandler($stream);
        $securityLogger->info($this->message, $context);
        $this->assertStringContainsString($this->message, $this->getLastContext($this->log_path));
    }
}