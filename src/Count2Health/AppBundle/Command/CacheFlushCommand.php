<?php

namespace Count2Health\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheFlushCommand extends ContainerAwareCommand
{

protected function configure()
{
$this->setName('c2h:cache:flush')
->setDescription('Flush memcache')
;
}

protected function execute(InputInterface $input, OutputInterface $output)
{
$cache = $this->getContainer()->get('memcache');

$result = $cache->flush();
if ($result) {
$output->writeln('Cache flushed successfully');
}
else {
$output->writeln('Could not flush the cache');
}
}

}