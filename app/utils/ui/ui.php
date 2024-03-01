<?php

use UI\Controls\Tab;
use UI\Size;
use UI\Window;

use function UI\run;

if (!extension_loaded('ui')) {
    die('UI extension not enabled.');
}
$window = new Window("test", new Size(320, 180), true);
$window->setMargin(true);

$tab = new Tab();

$window->add($tab);


$window->show();
run();
