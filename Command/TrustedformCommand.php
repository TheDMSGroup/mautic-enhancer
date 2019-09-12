<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticEnhancerBundle\Integration\TrustedFormIntegration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TrustedformCommand.
 */
class TrustedformCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:trustedform')
            ->setDescription('Claims Trustedform certificates offline.')
            ->addOption(
                '--thread-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of this current process if running multiple in parallel.',
                1
            )
            ->addOption(
                '--max-threads',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of processes you intend to run in parallel.',
                1
            )
            ->addOption(
                '--batch-limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts to process per round. Defaults to 100.',
                100
            )
            ->addOption(
                '--attempt-limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set the maximum number of times we will attempt to claim a certificate if rate limits or errors are encountered.',
                10
            );

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool|int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $threadId     = max(1, (int) $input->getOption('thread-id'));
        $maxThreads   = max(1, (int) $input->getOption('max-threads'));
        $batchLimit   = max(1, (int) $input->getOption('batch-limit'));
        $attemptLimit = max(1, (int) $input->getOption('attempt-limit'));

        if (!$this->checkRunStatus($input, $output, $this->getName().$threadId)) {
            $this->output->writeln('Already Running.');

            return 0;
        }

        if ($threadId > $maxThreads) {
            $this->output->writeln('--thread-id cannot be larger than --max-thread');

            return 1;
        }
        define('MAUTIC_PLUGIN_ENHANCER_CLI', true);

        try {
            /** @var TrustedFormIntegration $integration */
            $integration = $this->getContainer()->get('mautic.enhancer.integration.trustedform');
            $model       = $integration->getModel();

            if ($model->claimCertificates($threadId, $maxThreads, $batchLimit, $attemptLimit, $output)) {
                $output->writeln('Finished claiming certificates.');

                return 0;
            }
        } catch (\Exception $e) {
            $output->writeln('Trustedform certificate claiming failure '.$e->getMessage());
        }

        return 1;
    }
}
