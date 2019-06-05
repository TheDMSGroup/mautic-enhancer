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

use DateTime;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TrustedFormReprocessCommand.
 */
class TrustedFormReprocessCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this->setName('mautic:integration:enhancer:xverifyreprocess');
        $this->setDescription('Reprocessed leads with Xverify that failed.');
        $this->setHelp('shrug');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('<info>Pulling leads between 1.5 hours and 5 minutes ago...</info>');

            /** @var IntegrationHelper $integrationHelper */
            $integrationHelper = $this->getContainer()->get('mautic.helper.integration');
            $enhancerHelper    = new EnhancerHelper($integrationHelper);
            $trustedForm       = $enhancerHelper->getIntegration('TrustedForm');

            /** @var EntityManager $em */
            $em = $this->getContainer()->get(EntityManager::class);

            $repo = $em->getRepository('MauticLeadBundle:Lead');
            var_dump(get_class($repo));

            return;

            $dates = [(new DateTime('-1 hour -30 minutes'))->format('Y-m-d H:i:s'), (new DateTime('-5 minutes'))->format('Y-m-d H:i:s')];

            $leads = $repo->getEntities([
                'filter' => [
                    'where' => [
                        [
                            'column' => 'xx_trusted_form_cert_url',
                            'expr'   => 'isNotNull',
                        ],
                        [
                            'column' => 'date_added',
                            'expr'   => 'between',
                            'value'  => $dates,
                        ],
                    ],
                    'limit' => 1,
                ],
            ]);

            /** @var Lead $lead */
            $lead = array_pop($leads);

            /* $qb   = $repo->createQueryBuilder('l') */
            /*               ->andWhere() */
            /*               ->getQuery(); */
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
