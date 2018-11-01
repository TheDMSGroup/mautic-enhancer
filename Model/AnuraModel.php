<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Model;

use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Client;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerAnura;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerAnuraRepository;
use MauticPlugin\MauticEnhancerBundle\Integration\AnuraIntegration;

class AnuraModel extends AbstractCommonModel
{
    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $instance;

    /**
     * @param AnuraIntegration $integration
     */
    public function setup(AnuraIntegration $integration)
    {
        $keys           = $integration->getKeys();
        $this->endpoint = $keys['endpoint'];
        $this->instance = $keys['instance'];
    }

    /**
     * @return PluginEnhancerAnuraRepository|EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getEntityName());
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return PluginEnhancerAnura::class;
    }

    /**
     * @param $ipAddress
     * @param $userAgent
     *
     * @return string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getResult($ipAddress, $userAgent)
    {
        /** @var PluginEnhancerAnura $record */
        $record = $this->getRepository()->findByIpAndUserAgent($ipAddress, $userAgent);

        if (null === $record || 'failed' === $record->getResult()) {
            //perform lookup, save result
            $httpClient = new Client();

            $payload = [
                'instance' => $this->instance,
                'ip'       => $ipAddress,
                'ua'       => $userAgent,
            ];

            try {
                $response = $httpClient->request('GET', $this->endpoint, ['query' => $payload]);
                $result   = json_decode($response->getBody()->getContents(), true);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $result = ['result' => 'failed'];
            }

            if (null === $record) {
                $record = new PluginEnhancerAnura();
                $record
                    ->setIpAddress($ipAddress)
                    ->setUserAgent($userAgent);
            }
            $record
                ->setDateAdded(new \DateTime())
                ->setResult($result['result']);

            $this->getRepository()->saveEntity($record);
        }

        return $record->getResult();
    }
}
