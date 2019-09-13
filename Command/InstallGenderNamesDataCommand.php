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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallGenderNamesDataCommand.
 */
class InstallGenderNamesDataCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:installgendernames')
            ->setDescription('Imports the SSA historic top 1000 names for genders')
            ->setHelp(
                'This command will download and rebuild the GenderDictionary reference table. It uses the file located at https://www.ssa.gov/OACT/babynames/names.zip as its data source.'
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
        $details = '.';
        try {
            $model = $this->getContainer()->get('mautic.enhancer.model.gendername');
            if ($model->updateReferenceTable()) {
                $output->writeln('Reference data successfully loaded. GenderFromName is ready for use');

                return true;
            }
        } catch (\Exception $e) {
            $details = ', '.$e->getMessage().PHP_EOL;
        }
        $output->writeln('Failed to load reference table'.$details.' GenderFromName is not ready');

        return false;
    }
}
