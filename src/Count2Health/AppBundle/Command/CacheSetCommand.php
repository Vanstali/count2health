<?php

namespace Count2Health\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheSetCommand extends ContainerAwareCommand
{

protected function configure()
{
$this->setName('c2h:cache:set')
->setDescription('Set a key in memcache')
->addArgument('key', InputArgument::REQUIRED, 'The key to set')
->addArgument('value', InputArgument::REQUIRED, 'The value to set key to')
;
}

protected function execute(InputInterface $input, OutputInterface $output)
{
$key = $input->getArgument('key');
$value = $input->getArgument('value');
$cache = $this->getContainer()->get('memcache');

$result = $cache->set($key, $value);
if ($result) {
$output->writeln('Key set successfully');
}
else {
$output->writeln('Could not set key');
}
}

}