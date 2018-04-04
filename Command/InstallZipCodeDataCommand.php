<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/4/18
 * Time: 1:49 PM
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallZipCodeDataCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('plugins:enhancer-zipcode-import')
            ->setDescription('Imports allCountries.txt zipcode, city, state (US Primary only)')
            ->setHelp('final documentation for file state and location will be addressed here (i.e., downloaded, zipped, etc')
            ->addArgument('filename', InputArgument::REQUIRED, 'name of file to import');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument();
    }

}