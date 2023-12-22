<?php
namespace app\command;

use app\utils\SendEmail;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Hello extends Command
{
    protected function configure()
    {
        $this->setName('hello')
//            ->addArgument('name', Argument::OPTIONAL, "test")
            ->addOption('send-email', 'e', Option::VALUE_NONE, 'send email')
            ->setDescription('utils fun');
    }

    protected function execute(Input $input, Output $output)
    {
//        $name = trim($input->getArgument('name'));

        if ($input->hasOption('send-email')) {
//            $body = $input->getOption('send-email');
            (new SendEmail()) -> run();
        }

        $output->writeln("success");
    }
}