<?php


// php版本使用7.1，之后版本ui扩展无法加载，linux ui 扩展编译失败，原因暂未知
use UI\Area;
use UI\Controls\Box;
use UI\Draw\Color;
use UI\Draw\Path;
use UI\Draw\Pen;
use UI\Draw\Text\Font;
use UI\Draw\Text\Font\Descriptor;
use UI\Draw\Text\Layout;
use UI\Point;
use UI\Size;
use UI\Window;

if (!extension_loaded('ui')) {
    die('UI extension not enabled.');
}
$window = new Window("test", new Size(320, 180), false);
//$window->setMargin(true);

$box = new Box(Box::Vertical);
$window->add($box);

$font = new Font(new Descriptor("arial", 12));

new class ($box, $font) extends Area {
    private Font $font;

    public function __construct(Box $box, $font)
    {
        $box->append($this, true);
        $this->font = $font;
    }

    protected function onDraw(Pen $pen, Size $areaSize, Point $clipPoint, Size $clipSize)
    {
        $path = new Path();
        $path->addRectangle(Point::at(0), $areaSize);
        $path->end();
        //        $pen->fill($path, 255);// 黑色
        $color = new Color(255); // 值 0-255，影响alpha通道，值越大颜色越深，默认黑色
        //        $color->r = 1; // 红色 值0-1 ，影响对红色的转变
        //        $color->g = 1; // 绿色
        //        $color->b = 1; // 蓝色
        $pen->fill($path, $color);


        $path = new Path();
        //        $path->arcTo(new Point(160,90), 20, 7, 28, 0.2); // 弧形失败，原因未知
        $path->addRectangle(new Point(160, 90), new Size(10, 10));
        $path->end();
        $color->r = 1;
        $pen->fill($path, $color);


        $layout = new Layout('123456789', $this->font, 10);
        $layout->setColor($color, 2, 5);
        $pen->write(Point::at(0), $layout);
    }


};

$window->show();
UI\run();
