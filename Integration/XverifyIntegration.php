<?php
/*
 * @author      Scott Shipman
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Allow verification of a lead's email address using X-verify on a configurable
 * list of campaigns
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\Campaign;

class XverifyIntegration extends AbstractEnhancerIntegration
{
    const INTEGRATION_NAME = 'X-Verify';

    public function getAuthenticationType()
    {
        return 'api';
    }

    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    public function getDisplayName()
    {
        return self::INTEGRATION_NAME . ' Data Enhancer';
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        // fortemailverify.com
        return 'XVERIFY_DOMAIN';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        // 1003879-2410447D
        return 'XVERIFY_API_KEY';
    }

    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder
                ->add();
        }
        if ('features' === $formArea) {
            $builder
                ->add();
        }
    }

}