<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/4/18
 * Time: 1:49 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCityStatePostalCodeDataCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:installcspcdata')
            ->setDescription('Imports allCountries.txt postal code, city, state, and country')
            ->setHelp('This command will download and rebuild the CityStateFromPostalCode reference table. It uses the file located at http://download.geonames.org/export/zip/allCountries.zip as its data source.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel $cspcModel */
        $cspcModel = $this->getContainer()->get('mautic.enhancer.model.citystatepostalcode');
        $cspcModel->fillReferenceTable();
    }
}
