<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerTrustedformRepository;
use MauticPlugin\MauticEnhancerBundle\Model\TrustedformModel;

/**
 * Class LeadTimelineSubscriber.
 *
 * Currently only supports Trusteform enhancements.
 */
class LeadTimelineSubscriber extends CommonSubscriber
{
    /** @var TrustedformModel */
    protected $trustedFormModel;

    /**
     * LeadTimelineSubscriber constructor.
     *
     * @param TrustedformModel $trustedFormModel
     */
    public function __construct(TrustedFormModel $trustedFormModel)
    {
        $this->trustedFormModel = $trustedFormModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $eventTypeKey  = 'enhancer';
        $eventTypeName = 'Enhancer';
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Determine if this event has been filtered out
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var PluginEnhancerTrustedformRepository $repo */
        $repo = $this->trustedFormModel->getRepository();

        // $event->getQueryOptions() provide timeline filters, etc.
        // This method should use DBAL to obtain the events to be injected into the timeline based on pagination
        // but also should query for a total number of events and return an array of ['total' => $x, 'results' => []].
        // There is a TimelineTrait to assist with this. See repository example.$repo         = $this->em->getRepository('MauticContactSourceBundle:Event');
        $stats = $repo->getTimelineStats($event->getLeadId(), $event->getQueryOptions());

        // If isEngagementCount(), this event should only inject $stats into addToCounter() to append to data to generate
        // the engagements graph. Not all events are engagements if they are just informational so it could be that this
        // line should only be used when `!$event->isEngagementCount()`. Using TimelineTrait will determine the appropriate
        // return value based on the data included in getQueryOptions() if used in the stats method above.
        // $event->addToCounter($eventTypeKey, $stats);

        if (!$event->isEngagementCount()) {
            // Add the events to the event array
            foreach ($stats['results'] as $stat) {
                if ($stat['timestamp']) {
                    $event->addEvent(
                        [
                            // Event key type
                            'event'           => $eventTypeKey,
                            // Event name/label - can be a string or an array as below to convert to a link
                            'eventLabel'      => [
                                'label'  => ucwords(
                                        $stat['enhancer']
                                    ).(isset($stat['location']) ? ' / '.$stat['location'] : ''),
                                'href'   => (!empty($stat['share_url']) ? $stat['share_url'] : '#'),
                                'target' => '_blank',
                            ],
                            // Translated string displayed in the Event Type column
                            'eventType'       => $this->translator->trans(
                                'mautic.enhancer.integration.'.strtolower($stat['enhancer']).'.status.'.$stat['status']
                            ),
                            'timestamp'       => $stat['timestamp'],
                            'details'         => $stat,
                            'extra'           => [
                                'raw' => self::describeStat($stat),
                            ],
                            'contentTemplate' => 'MauticEnhancerBundle:Timeline:enhancer.html.php',
                            'icon'            => 'fa-plus-square-o enhancer-button',
                        ]
                    );
                }
            }
        }
    }

    /**
     * Moved here to pass tests and still support recursive enhancer data.
     *
     * @param     $value
     * @param int $depth
     *
     * @return string
     */
    private static function describeStat($value, $depth = 1)
    {
        $result = '<br/><dl class="dl-horizontal" style="padding-left: '.($depth * 10).'px;">';
        foreach ($value as $key => $val) {
            $result .= '<dt>'.$key.'</dt>';
            $result .= '<dd>';
            if (is_array($val) || is_object($val)) {
                $result .= self::describeStat($val, $depth + 1);
            } else {
                if (filter_var($val, FILTER_VALIDATE_URL)) {
                    $result .= '<a href='.$val.' target="_blank">'.$val.'</a>';
                } else {
                    $result .= $val;
                }
            }
            $result .= '</dd>';
        }
        $result .= '</dl>';

        return $result;
    }
}
