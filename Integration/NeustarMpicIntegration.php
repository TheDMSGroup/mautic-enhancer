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

class NeustarMpicIntegration extends AbstractNeustarIntegration
{
    protected function getNeustarElementId()
    {
        return '3226';
    }

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

    protected function getAvailableResponses()
    {
        return [
            'address' => ['score'],
            'phone'   => ['score', 'appends' => ['validation', 'mobile']],
            'email'   => ['score'],
        ];
    }

    protected function getServiceData(Lead $lead, $serviceId = '875')
    {
        // I would like to enable campaign config for the fields (phone, email, address, IP)
        // and the append options (see Element ID 3226.pdf.
        // for now, just address[score]
        // $settings = $this->getIntegrationSettings()->getFeatureSettings();

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
            $phone->setAttribute('appends', 'validation,mobile');

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
        $data = trim($response->getBody()->getContents());

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
                        $fieldNameBase = strtolower(sprintf('%s_%s_%s_', self::NEUSTAR_PREFIX, $this->getNeustarIntegrationName(), $field));
                        foreach ($attributes as $attribute => $value) {
                            $lead->addUpdatedField($fieldNameBase.$attribute, $value);
                        }
                    }
                }
            }
        }

        return true;
    }
}
