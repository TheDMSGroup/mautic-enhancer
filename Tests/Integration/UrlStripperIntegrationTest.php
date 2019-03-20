<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/20/19
 * Time: 9:37 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\UrlStripperIntegration;
use PHPUnit\Framework\TestCase;

class UrlStripperIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $settings = new Integration();
        $settings->setFeatureSettings(['original_consent_url' => 'consent_url_dirty']);
        $lead                    = new Lead();
        $expectedUrl             = 'https://www.example.com';
        $dirtyUrl                = $expectedUrl.'?querystring=';
        $lead->consent_url_dirty = $dirtyUrl;
        $mock                    = $this->createMock(UrlStripperIntegration::class);
        $mock->expects($this->any())
            ->method('getIntegrationSettings')
            ->will($this->returnValue($settings));

        $this->assertTrue($mock->doEnhancement($lead), 'Unexpected result');
        $this->assertEquals($expectedUrl, $lead->getFieldValue('consent_url_clean'));

        $this->assertFalse($mock->doEnhancement($lead), 'Unexpected result');
    }
}
