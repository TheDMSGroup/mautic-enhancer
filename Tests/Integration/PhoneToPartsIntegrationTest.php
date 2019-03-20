<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/20/19
 * Time: 9:07 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\PhoneToPartsIntegration;
use PHPUnit\Framework\TestCase;

class PhoneToPartsIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $lead = new Lead();
        $lead->setPhone('9876543210');
        $mock   = $this->createMock(PhoneToPartsIntegration::class);
        $fields = [
            'ptp_area_code'   => '987',
            'ptp_prefix'      => '654',
            'ptp_line_number' => '3210',
        ];

        $this->assertTrue($mock->doEnhancement($lead));
        foreach ($fields as $field => $expected) {
            $this->assertEquals($expected, $lead->getFieldValue($field), 'Unexpected phone part');
        }

        $lead->setPhone(null);
        $lead->setMobile(('1234567890'));
        $fields = [
            'ptp_area_code'   => '987',
            'ptp_prefix'      => '654',
            'ptp_line_number' => '3210',
        ];

        $this->assertTrue($mock->doEnhancement($lead));
        foreach ($fields as $field => $expected) {
            $this->assertEquals($expected, $lead->getFieldValue($field), 'Unexpected phone part');
        }
    }
}
