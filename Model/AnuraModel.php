<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 10/18/18
 * Time: 4:26 PM
 */

namespace MauticEnhancerBundle\Model;


use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerAnura;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerAnuraRepository;

class AnuraModel extends AbstractCommonModel
{
    /**
     * @return PluginEnhancerAnuraRepository|EntityRepository
     */
    public function getRepository() {
        return $this->em->getRepository(PluginEnhancerAnura::class);
    }

    /**
     * @param string $ipAddress
     * @param string $userAgent
     *
     * @return bool
     */
    public function isSuspicious($ipAddress, $userAgent)
    {
        $check = $this->getRepository()->findByIpAndUserAgent($ipAddress, $userAgent);

        if (null === $check) {
            //perform lookup, save result

        }

        return $check;
    }


}
