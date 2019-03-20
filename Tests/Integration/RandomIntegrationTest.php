<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/20/19
 * Time: 8:06 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\RandomIntegration;
use PHPUnit\Framework\TestCase;

class RandomIntegrationTest extends TestCase
{
    public function testDoEnhancementNew()
    {
        $lead     = new Lead();
        $mock     = $this->createMock(RandomIntegration::class);
        $settings = new Integration();
        $settings->setFeatureSettings(['random_field_name' => 'random']);
        $mock->expects($this->any())
            ->method('getIntegrationSettings')
            ->will($this->returnValue($settings));

        $this->assertTrue($mock->doEnhancement($lead), 'Unexpected result');
        $actual = $lead->getFieldValue('random');
        $this->assertGreaterThan(0, $actual, 'Unexpected random value');
        $this->assertLessThanOrEqual(100, $actual, 'Unexpected random value');

        //random should not overwrite existing value
        $this->assertFalse($mock->doEnhancement($lead), 'Unexpected result');
        $this->assertEquals($actual, $lead->getFieldValue('random'));
    }
}
