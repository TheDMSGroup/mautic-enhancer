<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 8/8/18
 * Time: 10:08 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

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

        $names = $xmlDoc->createElement('Names')->appendChild($name);
        $root->appendChild($names);

        if ($lead->getAddress1()) {
            $address = $xmlDoc->createElement('Address');
            $address->setAttribute('score', '1');
            $address->appendChild($xmlDoc->createElement('Street', $lead->getAddress1()));
            $address->appendChild($xmlDoc->createElement('City', $lead->getCity()));
            $address->appendChild($xmlDoc->createElement('ST', $lead->getState()));
            $address->appendChild($xmlDoc->createElement('postal', $lead->getZipcode()));

            $addresses = $xmlDoc->createElement('Addresses')->appendChild($address);
            $root->appendChild($addresses);
        }

        if ($lead->getPhone()) {
            $phone = $xmlDoc->createElement('Phone', $lead->getPhone());
            $phone->setAttribute('score', '1');
            $phone->setAttribute('appends', 'validation,mobile');

            $phones = $xmlDoc->createElement('Phones')->appendChild($phone);
            $root->appendChild($phones);
        }

        if ($lead->getEmail()) {
            $email = $xmlDoc->createElement('eMail', $lead->getEmail());
            $email->setAttribute('score', '1');

            $emails = $xmlDoc->createElement('eMailAddresses')->appendChild($email);
            $root->appendChild($emails);
        }

        $xmlDoc->appendChild($root);

        return $xmlDoc->saveXML();
    }

    protected function processResponse($response)
    {
        return;
    }
}
