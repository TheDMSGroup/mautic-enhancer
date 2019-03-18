<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/18/19
 * Time: 1:46 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\AgeFromBirthdateIntegration;
use PHPUnit\Framework\TestCase;

class AgeFromBirthdareIntegrationTest extends TestCase
{
    public function testDoEnhancementWithDOB()
    {
        $lead      = new Lead();
        $lead->dob = new \DateTime('1970-01-01');
        $today     = getdate();
        $expected  = $today['year'] - 1970;

        $mock = $this->createMock(AgeFromBirthdateIntegration::class);
        $mock->doEnhancement($lead);

        $this->assertEquals($expected, $lead->getFieldValue('afb_age'), 'Unexpected age');
        $this->assertEquals(1970, $lead->getFieldValue('dob_year'), 'Unexpected birth year');
        $this->assertEquals(1, $lead->getFieldValue('dob_month'), 'Unexpected birth month');
        $this->assertEquals(1, $lead->getFieldValue('dob_day'), 'Unexpected birth day');
    }

    public function testDoEnhancementWithYMD()
    {
        $lead            = new Lead();
        $lead->dob_year  = 1970;
        $lead->dob_month = 1;
        $lead->dob_day   = 1;

        $mock = $this->createMock(AgeFromBirthdateIntegration::class);
        $mock->doEnhancement($lead);

        $today       = getdate();
        $expectedAge = $today['year'] - 1970;
        $expectedDOB = new \DateTime('1970-01-01');

        $this->assertEquals($expectedAge, $lead->getFieldValue('afb_age'), 'Unexpected age');
        $this->assertEquals($expectedDOB, $lead->getFieldValue('dob'), 'Unexpected birth date');
    }
}
