<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerBlacklist;
use MauticPlugin\MauticEnhancerBundle\Model\TrustedformModel;

// use Mautic\LeadBundle\Entity\UtmTag;

class TrustedFormIntegration extends AbstractEnhancerIntegration
{
    /* @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait */
    use NonFreeEnhancerTrait {
        appendToForm as appendNonFreeFields;
    }

    /** @var string */
    const CERT_REAL_HOST = 'cert.trustedform.com';

    /** @var string */
    const CERT_URL_FIELD = 'xx_trusted_form_cert_url';

    /** @var TrustedformModel */
    protected $integrationModel;

    /**
     * @return string
     */
    public function getName()
    {
        return 'TrustedForm';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Trusted Form';
    }

    /**
     * @param Contact $contact
     *
     * @return bool
     */
    public function doEnhancement(Contact $contact)
    {
        $persist = false;
        if ($contact) {
            // Instead of modifying the lead in realtime, we'll queue the certificate claiming for a parallel process.
            try {
                /** @var PluginEnhancerBlacklist $record */
                $records = $this->getModel()->queueContact($contact);
                if ($records) {
                    $persist = true;
                }
            } catch (\Exception $exception) {
                $this->handleEnchancerException('Trustedform', $exception);
                $this->logger->error('Trustedform Enhancer: '.$exception->getMessage());
            }
        }

        return $persist;
    }

    /**
     * @return \Mautic\CoreBundle\Model\AbstractCommonModel|TrustedformModel
     */
    public function getModel()
    {
        if (!isset($this->integrationModel)) {
            $this->integrationModel = $this->factory->getModel('enhancer.trustedform');
            $this->integrationModel->setup($this);
        }

        return $this->integrationModel;
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback).
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'basic';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder->add(
                'realtime',
                'boolean',
                [
                    'label'      => $this->translator->trans('mautic.enhancer.integration.trustedform.realtime.label'),
                    'data'       => !empty($data['realtime']) ? (bool) $data['realtime'] : true,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans(
                            'mautic.enhancer.integration.trustedform.realtime.tooltip'
                        ),
                    ],
                ]
            );
        }
        $this->appendNonFreeFields($builder, $data, $formArea, true);
    }

    /**
     * @returns array[]
     */
    protected function getEnhancerFieldArray()
    {
        return [
            self::CERT_URL_FIELD      => [
                'type'  => 'url',
                'label' => 'Trusted Form Cert',
            ],
            'trusted_form_created_at' => [
                'type'  => 'datetime',
                'label' => 'Trusted Form Cert Claimed',
            ],
            'trusted_form_expires_at' => [
                'type'  => 'datetime',
                'label' => 'Trusted Form Cert Expires',
            ],
            'trusted_form_share_url'  => [
                'type'  => 'url',
                'label' => 'Trusted Form Shareable Cert',
            ],
        ];
    }
}
