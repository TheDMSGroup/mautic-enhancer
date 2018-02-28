<?php

namespace MauticPlugin\MauticEnhancerBundle\Integration;

interface NonFreeEnhancerInterface
{
    public function getAutorunEnabled();

    public function getCostPerEnhancement();    
}