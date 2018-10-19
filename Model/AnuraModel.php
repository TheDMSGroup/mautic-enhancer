<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 10/18/18
 * Time: 4:26 PM
 */

namespace MauticEnhancerBundle\Model;


use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Client;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerAnura;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerAnuraRepository;
use MauticPlugin\MauticEnhancerBundle\Integration\AnuraIntegration;

class AnuraModel extends AbstractCommonModel
{
    protected $endpoint;

    protected $instance;

    /**
     * @return PluginEnhancerAnuraRepository|EntityRepository
     */
    public function getRepository() {
        return $this->em->getRepository(PluginEnhancerAnura::class);
    }

    /**
     * @param AnuraIntegration $integration
     *
     * @return bool
     */
    public function setup(AnuraIntegration $integration)
    {
        try {
            $keys = $integration->getKeys();
            $this->endpoint = $keys['endpoint'];
            $this->instance = $keys['instance'];
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * @param string $ipAddress
     * @param string $userAgent
     *
     * @return bool
     */
    public function isSuspect($ipAddress, $userAgent)
    {
        $check = $this->getRepository()->findByIpAndUserAgent($ipAddress, $userAgent);

        if (null === $check) {
            //perform lookup, save result
            $httpClient = new Client([
                'base_uri' => $endpoint,
                'timeout'  => 2.0,
            ]);

            $response = $httpClient->request('POST', '');
            $check = $response[''];

            $record = new PluginEnhancerAnura();
            $record
                ->setDateAdded(new \DateTime())
                ->setIpAddress($ipAddress)
                ->setUserAgent($userAgent)
                ->setIsSuspect($check);
            $this->getRepository()->saveEntity($record);
        }

        return $check;
    }


}
