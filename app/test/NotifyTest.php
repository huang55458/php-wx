<?php

namespace app\test;

use Joli\JoliNotif\DefaultNotifier;
use Joli\JoliNotif\Notification;
use PHPUnit\Framework\TestCase;

class NotifyTest extends TestCase
{
    public function testNotify(): void
    {
        $notifier = new DefaultNotifier();

        $notification =
            (new Notification())
                ->setTitle('Notification title')
                ->setBody('This is the body of your notification')
                ->setIcon('C:\Users\Administrator\Pictures\1.png')
//                ->addOption('subtitle', 'This is a subtitle') // Only works on macOS (AppleScriptDriver)
//                ->addOption('sound', 'Frog') // Only works on macOS (AppleScriptDriver)
        ;

        $this->assertEquals(true, $notifier->send($notification));
    }
}