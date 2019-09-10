<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallCityStatePostalCodeDataCommand.
 */
class InstallCityStatePostalCodeDataCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:installcspcdata')
            ->setDescription('Imports postal code, city, state, county, latitude, longitude and country')
            ->setHelp(
                'This command will download and rebuild the CityStateFromPostalCode reference table. It uses the file located at http://download.geonames.org/export/zip/allCountries.zip as its data source.'
            );

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var CityStatePostalCodeModel $model */
            $model = $this->getContainer()->get('mautic.enhancer.model.citystatepostalcode');
            if ($model->updateReferenceTable()) {
                $output->writeln('Reference data successfully loaded. CityStateFromPostalCode is ready for use.');

                return true;
            }
        } catch (\Exception $e) {
            $output->write('CityStatePostalCode: '.$e->getMessage());
        }
        $output->writeln('Failed to load reference table. CityStateFromPostalCode is not ready.');

        return false;
    }
}
