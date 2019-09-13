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
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EnhanceCommand.
 */
class EnhanceCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:enhance')
            ->setDescription('Runs one or more enhancers on leads.')
            ->addOption(
                '--enhancers',
                null,
                InputOption::VALUE_OPTIONAL,
                'A list of the enhancers to run, comma delimited. Leave blank to run all auto-run enhancers.',
                null
            )
            ->addOption(
                '--enhancer',
                null,
                InputOption::VALUE_OPTIONAL,
                'An enhancers to run. Leave blank to run all auto-run enhancers.',
                null
            )
            ->addOption(
                'contact-id',
                null,
                InputOption::VALUE_REQUIRED,
                'The id of a contact/lead to process.',
                null
            )
            ->addOption(
                'contact-ids',
                null,
                InputOption::VALUE_REQUIRED,
                'The ids of a contacts/leads to process, comma separated',
                null
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool|int|null
     *
     * @throws ApiErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options    = $input->getOptions();
        $contactIds = [];
        if (!empty($options['contact-ids'])) {
            $contactIds = explode(',', $options['contact-ids']);
            array_walk($contactIds, 'intval');
        } elseif (!empty($options['contact-id']) && is_numeric($options['contact-id'])) {
            $contactIds = [(int) $options['contact-id']];
        }
        if (!$contactIds) {
            $output->writeln('Must have at least one contact ID to process.');

            return true;
        }

        $enhancers = [];
        if (!empty($options['enhancers'])) {
            $enhancers = explode(',', $options['enhancers']);
            array_walk($options['enhancers'], 'trim');
            array_walk($options['enhancers'], 'strtolower');
        } elseif (!empty($options['enhancer']) && strlen(trim($options['enhancer']))) {
            $enhancers = [strtolower(trim($options['enhancer']))];
        }
        $integrations      = $this->getContainer()->get('mautic.enhancer.helper.enhancer')->getEnhancerIntegrations();
        $integrationsToRun = [];
        /** @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration $integration */
        foreach ($integrations as $integration) {
            $demandedEnhancer = in_array(strtolower($integration->getName()), $enhancers);
            if (!$enhancers || $demandedEnhancer) {
                $settings = $integration->getIntegrationSettings();
                if ($settings->getIsPublished()) {
                    $features = $settings->getFeatureSettings();
                    if ($demandedEnhancer || (isset($features['autorun_enabled']) && $features['autorun_enabled'])) {
                        $integrationsToRun[] = $integration;
                        $output->writeln('Will run Enhancer '.$integration->getName());
                    }
                }
            }
        }
        unset($integration);

        /** @var LeadModel $contactModel */
        $contactModel = $this->getContainer()->get('mautic.lead.model.lead');
        foreach ($contactIds as $contactId) {
            $contact = $contactModel->getEntity($contactId);
            if ($contact) {
                foreach ($integrationsToRun as $integration) {
                    if ($integration->doEnhancement($contact)) {
                        $contactModel->saveEntity($contact);
                    }
                }
            }
        }

        return false;
    }
}
