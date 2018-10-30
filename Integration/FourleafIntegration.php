<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class FourleafIntegration.
 */
class FourleafIntegration extends AbstractEnhancerIntegration
{
    /* @var NonFreeEnhancerInterface */
    use NonFreeEnhancerTrait;

    /**
     * @return string
     */
    public function getName()
    {
        return 'Fourleaf';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Email Engagement Scoring by '.$this->getName();
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        $integrationFields = [
            'id'  => $this->translator->trans('mautic.enhancer.integration.fourleaf.id.label'),
            'key' => $this->translator->trans('mautic.enhancer.integration.fourleaf.key.label'),
            'url' => $this->translator->trans('mautic.enhancer.integration.fourleaf.url.label'),
        ];

        return $integrationFields;
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'fourleaf_algo'           => [
                'label' => 'Fourleaf Algo',
                'type'  => 'text',
            ],
            'fourleaf_low_intel'      => [
                'label' => 'Fourleaf Low Intel',
                'type'  => 'boolean',
            ],
            'fourleaf_activity_score' => [
                'label' => 'Fourleaf Activity Score',
                'type'  => 'number',
            ],
            'fourleaf_hygiene_reason' => [
                'label' => 'Fourleaf Hygiene Reason',
                'type'  => 'text',
            ],
            'fourleaf_hygiene_score'  => [
                'label' => 'Fourleaf Hygiene Score',
                'type'  => 'number',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead &$lead)
    {
        $persist = false;
        if (!empty($lead)) {
            $algo  = $lead->getFieldValue('fourleaf_algo');
            $email = $lead->getEmail();

            if ($algo || !$email) {
                return false;
            }

            $keys = $this->getDecryptedApiKeys();

            // @todo - Update to use Guzzle.
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $keys['url'].'?ident='.$lead->getEmail(),
                CURLOPT_HTTPHEADER     => [
                    "X-Fourleaf-Id: $keys[id]",
                ],
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT        => 3,
            ];

            try {
                $ch = curl_init();
                curl_setopt_array($ch, $options);
                $response = curl_exec($ch);
                curl_close($ch);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());

                return false;
            }

            $response = json_decode($response, true);

            if ('ok' === $response['message']) {
                $this->applyCost($lead);
                $data           = $response['data'];
                $allowedAliases = $this->getEnhancerFieldArray();
                unset($data['md5']);
                $data['low_intel'] = (bool) strcasecmp($response['data']['low_intel'], 'false');
                foreach ($data as $key => $value) {
                    $alias = 'fourleaf_'.str_replace('user_', '', $key);
                    if (isset($allowedAliases[$alias])) {
                        $default = $lead->getFieldValue($alias);
                        $lead->addUpdatedField($alias, $value, $default);
                    }
                }
                $persist = true;
            }
        }

        return $persist;
    }
}
