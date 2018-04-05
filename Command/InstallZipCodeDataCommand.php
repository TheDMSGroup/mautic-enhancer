<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/4/18
 * Time: 1:49 PM
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginsEnhancerZipCityState;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallZipCodeDataCommand extends ModeratedCommand
{

    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:zipcode-import')
            ->setDescription('Imports allCountries.txt zipcode, city, state (US Primary only)')
            ->setHelp('final documentation for file state and location will be addressed here (i.e., downloaded, zipped, etc')
            ->addArgument('filename', InputArgument::REQUIRED, 'name of file to import');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filename = $input->getArgument('filename');
        if (!file_exists($filename)) {
            // determine required stream interfaces?
        }

        $file = fopen($filename, 'r');
        if (!$file) {

        }

        $batch_size = 100;
        $count = 0;
        while($data = fgetcsv($file, '1024', "\t")) {
            if ('US' !== $data[0] || preg_match('#^[AF]?P[RO]$#',$data[2])) {
                //skips non-us, PR, and military bases
                continue;
            }
            $zipCityState = new PluginsEnhancerZipCityState();
            $zipCityState
                ->setZipcode($data[1])
                ->setState($data[2])
                ->setCity($data[3]);
            $em->persist($zipCityState);
            if (0 === ($count % $batch_size)) {
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        $em->clear();
    }

}