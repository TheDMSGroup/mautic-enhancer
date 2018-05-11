<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/11/18
 * Time: 11:56 AM
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

namespace MauticPlugin\MauticEnhancerBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallGenderDictionaryCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:installgenderdictionary')
            ->setDescription('Imports the SSA tope name,gender data')
            ->setHelp('This command will download and rebuild the GenderDictionary reference table. It uses the file located at https://www.ssa.gov/OACT/babynames/names.zip as its data source.');
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
            $model = $this->getContainer()->get('mautic.enhancer.model.genderdictionary');
            if ($model->updateReferenceTable()) {
                $output->writeln('Reference data successfully loaded. GenderDictionary is ready for use.');

                return true;
            }
        } catch (\Exception $e) {
        }
        $output->writeln('Failed to load reference table. GenderDictionary is not ready.');

        return false;
    }

}