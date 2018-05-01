# Mautic Enhancer [![Latest Stable Version](https://poser.pugx.org/thedmsgroup/mautic-enhancer-bundle/v/stable)](https://packagist.org/packages/thedmsgroup/mautic-enhancer-bundle) [![License](https://poser.pugx.org/thedmsgroup/mautic-enhancer-bundle/license)](https://packagist.org/packages/thedmsgroup/mautic-enhancer-bundle) [![Build Status](https://travis-ci.org/TheDMSGroup/mautic-enhancer.svg?branch=master)](https://travis-ci.org/TheDMSGroup/mautic-enhancer)

![Age from Birthdate](./Assets/img/agefrombirthdate.png)![Alcazar](./Assets/img/alcazar.png)![Fourleaf](./Assets/img/fourleaf.png)![Random](./Assets/img/random.png)![Xverify](./Assets/img/xverify.png)

A bundle of contact data enhancers.

## External Enhancers

- Alcazar - Phone data lookup
- Fourleaf - Contact activity and hygiene scoring
- XVerify - Validates email and phone fields

## Local Enhancers

- Random - Stores random number for use in A/B splits
- Age from Birthdate - Stores age based on a birthdate field
- City & State from Postal Code - Backfills blank city/state fields when the postal(zip) is filled. IP Address data can be used to enhance the data.

## Installation & Usage

Currently being tested with Mautic `2.12.x`.
If you have success/issues with other versions please report.

1. Install by running `composer require thedmsgroup/mautic-enhancer-bundle` or by unpacking this repository's contents into a folder named `/plugins/MauticEnhancerBundle`
2. Go to `/s/plugins` and click `Install/Upgrade Plugins`.
3. Publish and configure the integrations as you wish.
