<?php

namespace Count2Health\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheGetCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('c2h:cache:get')
->setDescription('Get a value from memcache')
->addArgument('key', InputArgument::REQUIRED, 'The key to get');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $cache = $this->getContainer()->get('memcache');

        $output->writeln($cache->get($key));
    }
}
