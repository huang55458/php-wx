<?php

namespace app\test;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CarbonTest extends TestCase
{
    public function test1()
    {
        printf("Right now is %s\n", Carbon::now()->toDateTimeString());
        printf("Right now in Vancouver is %s\n", Carbon::now('America/Vancouver'));  //implicit __toString()
        $tomorrow = Carbon::now()->addDay();
        printf("last day is %s\n", $tomorrow->toDateTimeString());
        $lastWeek = Carbon::now()->subWeek();
        printf("previous week is %s\n", $lastWeek->toDateTimeString());
        $officialDate = Carbon::now()->toRfc2822String();
        printf("officialDate %s\n", $officialDate);
        $howOldAmI = Carbon::createFromDate(1975, 5, 21)->age;
        printf("howOldAmI %s\n", $howOldAmI);
        $noonTodayLondonTime = Carbon::createFromTime(12, 0, 0, 'Asia/Shanghai');
        printf("noonTodayLondonTime %s\n", $noonTodayLondonTime->toDateTimeString());

        $internetWillBlowUpOn = Carbon::create(2038, 01, 19, 3, 14, 7, 'GMT');
        Carbon::setTestNow(Carbon::createFromDate(2000, 1, 1));
        if (Carbon::now()->gte($internetWillBlowUpOn)) {
            die();
        }

        // Phew! Return to normal behaviour
        Carbon::setTestNow();
        if (Carbon::now()->isWeekend()) {
            echo 'Party!' . PHP_EOL;
        }

        // Over 200 languages (and over 500 regional variants) supported:
        echo Carbon::now()->subMinutes(2)->diffForHumans() . PHP_EOL; // '2 minutes ago'
        echo Carbon::now()->subMinutes(2)->locale('zh_CN')->diffForHumans() . PHP_EOL; // '2分钟前'
        echo Carbon::parse('2019-07-23 14:51')->isoFormat('LLLL') . PHP_EOL; // 'Tuesday, July 23, 2019 2:51 PM'
        $time = Carbon::parse('2019-07-23 14:51')->locale('zh_CN')->isoFormat('LLLL') . PHP_EOL;
        $this->assertStringContainsString('2019年7月23日星期二下午2点51分', $time);

        $daysSinceEpoch = Carbon::createFromTimestamp(0)->diffInDays();
        printf("daysSinceEpoch %s\n", $daysSinceEpoch);
        $daysUntilInternetBlowUp = $internetWillBlowUpOn->diffInDays();
        printf("daysUntilInternetBlowUp %s\n", $daysUntilInternetBlowUp);
        Carbon::createFromTimestamp(0)->diffInDays($internetWillBlowUpOn);
    }
}