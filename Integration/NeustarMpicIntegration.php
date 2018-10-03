<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 8/8/18
 * Time: 10:08 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use GuzzleHttp\Psr7\Response;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class NeustarMpicIntegration.
 */
class NeustarMpicIntegration extends AbstractNeustarIntegration
{
    /**
     * @return string
     */
    protected function getNeustarElementId()
    {
        return '3226';
    }

    /**
     * @return string
     */
    protected function getNeustarIntegrationName()
    {
        return 'Mpic';
    }

    /**
     * @return array
     */
    protected function getNeustarServiceKeys()
    {
        return ['875'];
    }

    /**
     * @return array
     */
    public function getEnhancerFieldArray()
    {
        return [
            'neustar_mpic_address_score'    => [
                'label' => 'Neustar Address Score',
                'type'  => 'number',
            ],
            'neustar_mpic_phone_score'      => [
                'label' => 'Neustar Phone Score',
                'type'  => 'number',
            ],
            'neustar_mpic_phone_validation' => [
                'label' => 'Neustar Phone Is Valid',
                'type'  => 'boolean',
            ],
            'neustar_mpic_phone_mobile'     => [
                'label' => 'Neustar Phone Is Mobile',
                'type'  => 'boolean',
            ],
            'neustar_mpic_phone_active'     => [
                'label' => 'Neustar Phone Is Active',
                'type'  => 'boolean',
            ],
            'neustar_mpic_email_score'      => [
                'label' => 'Neustar Email Score',
                'type'  => 'number',
            ],
        ];
    }

    /**
     * @param Lead   $lead
     * @param string $serviceId
     *
     * @return string
     */
    protected function getServiceIdData(Lead $lead, $serviceId = '875')
    {
        $xmlDoc                     = new \DOMDocument('1.0');
        $xmlDoc->preserveWhiteSpace = false;

        $root = $xmlDoc->createElement('Contact');

        $name = $xmlDoc->createElement('Name');
        $name->setAttribute('type', 'C');
        $name->appendChild($xmlDoc->createElement('First', $lead->getFirstname()));
        $name->appendChild($xmlDoc->createElement('Last', $lead->getLastname()));

        $names = $xmlDoc->createElement('Names');
        $names->appendChild($name);
        $root->appendChild($names);

        if ($lead->getAddress1()) {
            $address = $xmlDoc->createElement('Address');
            $address->setAttribute('score', '1');
            $address->appendChild($xmlDoc->createElement('Street', $lead->getAddress1()));
            $address->appendChild($xmlDoc->createElement('City', $lead->getCity()));
            $address->appendChild($xmlDoc->createElement('ST', $lead->getState()));
            $address->appendChild($xmlDoc->createElement('postal', $lead->getZipcode()));

            $addresses = $xmlDoc->createElement('Addresses');
            $addresses->appendChild($address);
            $root->appendChild($addresses);
        }

        if ($lead->getPhone()) {
            $phone = $xmlDoc->createElement('Phone', $lead->getPhone());
            $phone->setAttribute('score', '1');
            $phone->setAttribute('appends', 'validation,mobile,active');

            $phones = $xmlDoc->createElement('Phones');
            $phones->appendChild($phone);
            $root->appendChild($phones);
        }

        if ($lead->getEmail()) {
            $email = $xmlDoc->createElement('eMail', $lead->getEmail());
            $email->setAttribute('score', '1');

            $emails = $xmlDoc->createElement('eMailAddresses');
            $emails->appendChild($email);
            $root->appendChild($emails);
        }

        $xmlDoc->appendChild($root);

        return $xmlDoc->saveXML();
    }

    /**
     * @param Response $response
     */
    protected function processResponse(Lead $lead, Response $response)
    {
        try {
            $data        = trim($response->getBody()->getContents());
            $xdgResponse = new \SimpleXMLElement($data);

            if ('0' === ''.$xdgResponse->errorcode) {
                $result = $xdgResponse->response->result;
                if ('0' === ''.$result->errorcode) {
                    $contact = new \DOMDocument();
                    $contact->recover;
                    $contact->loadXML($result->value);
                    $contact = $this->domDocumentArray($contact);

                    foreach ($contact['Contact'] as $section => $result) {
                        switch ($section) {
                            case 'Addresses':
                                $field      = 'address';
                                $attributes = isset($result['Address']['@attributes'])
                                    ? $result['Address']['@attributes']
                                    : [];
                                break;
                            case 'Phones':
                                $field      = 'phone';
                                $attributes = isset($result['Phone']['@attributes'])
                                    ? $result['Phone']['@attributes']
                                    : [];
                                if (!empty($attributes)) {
                                    $attributes['active']     = (bool) $attributes['active'];
                                    $attributes['validation'] = (bool) $attributes['validation'];
                                    $attributes['mobile']     = ('Y' === $attributes['mobile']);
                                }
                                break;
                            case 'eMailAddresses':
                                $field      = 'email';
                                $attributes = isset($result['eMail']['@attributes'])
                                    ? $result['eMail']['@attributes']
                                    : [];
                                break;
                            default:
                                $field      = false;
                                $attributes = [];
                        }

                        if ($field) {
                            $fieldNameBase = strtolower(
                                sprintf(
                                    '%s_%s_%s_',
                                    self::NEUSTAR_PREFIX,
                                    $this->getNeustarIntegrationName(),
                                    $field
                                )
                            );
                            foreach ($attributes as $attribute => $value) {
                                $default = $lead->getFieldValue($fieldNameBase.$attribute);
                                $lead->addUpdatedField($fieldNameBase.$attribute, $value, $default);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('%s (%s): %s', __FILE__, __LINE__, $e->getMessage()));

            return false;
        }

        return true;
    }
}
