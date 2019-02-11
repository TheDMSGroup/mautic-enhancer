<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 2/11/19
 * Time: 11:56 AM
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;


use Mautic\LeadBundle\Entity\Lead;

class TrustedFormIntegration extends AbstractEnhancerIntegration
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'trustedform';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Trusted Form';
    }
    
    /**
     * @returns array[]
     */
    protected function getEnhancerFieldArray()
    {
        // TODO: Implement getEnhancerFieldArray() method.
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead $lead)
    {
        // TODO: Implement doEnhancement() method.
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback).
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        // TODO: Implement getAuthenticationType() method.
    }
}
