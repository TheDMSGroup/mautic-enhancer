<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/19/19
 * Time: 8:53 AM
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;


use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\AlcazarIntegration;
use PHPUnit\Framework\TestCase;

class AlcazarIntegrationTest extends TestCase
{

    public function testDoEnhancement()
    {
        $settings = new Integration();
        $settings->setFeatureSettings(['dnc' => 1, 'extended' => 1, 'output' => 'json',]);
        $mock = $this->createMock(AlcazarIntegration::class);

        $mock->expects($this->any())
            ->method('getIntegrationSettings')
            ->will($this->returnValue($settings));


    }
}