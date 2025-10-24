<?php

namespace Icinga\Module\Perfdatagraphs\Widget;

use Icinga\Application\Config;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\I18n\Translation;
use ipl\Web\Widget\Icon;

/**
 * The QuickActions adds links for selecting the duration.
 */
class QuickActions extends BaseHtmlElement
{
    use Translation;

    protected $timeranges = [];

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'quick-actions'];

    // The URL parameter we append to the URL to specify the duration the backend should fetch
    protected $rangeURLParam = 'perfdatagraphs.duration';

    protected $baseURL;

    /**
     * @param Url $baseURL URL to use as base for the links. We get this from the request
     * so that we can support IcingaDB and the monitoring module.
     */
    public function __construct(Url $baseURL)
    {
        $this->baseURL = $baseURL;

        $this->timeranges = $this->getDefaultTimeranges();

        $configuredRanges = $this->getConfigTimeranges();

        if (count($configuredRanges) > 1) {
            $this->timeranges = $configuredRanges;
        }
    }

    /**
     * getConfigTimeranges returns the timeranges from the configuration
     */
    protected function getConfigTimeranges(): array
    {
        $tr = Config::module('perfdatagraphs', 'timeranges');
        $timeranges = [];

        foreach ($tr->keys() as $range) {
            $timeranges[$range] = [
                    'display_name' => $tr->get($range, 'display_name', $range),
                    'href_title' => $tr->get($range, 'href_title', ''),
                    'href_icon' => $tr->get($range, 'href_icon', 'calendar'),
            ];
        }

        return $timeranges;
    }

    /**
     * getDefaultTimeranges returns a default set of timeranges
     */
    protected function getDefaultTimeranges(): array
    {
        $defaultCurrentRange = 'PT12H';
        $config = Config::module('perfdatagraphs');

        if ($config !== null) {
                $defaultCurrentRange = $config->get('perfdatagraphs', 'default_timerange', 'PT12H');
        }

        return [
            $defaultCurrentRange => [
                "display_name" => $this->translate("Current"),
                "href_title" => $this->translate("Show performance data for the 12 hours"),
                "href_icon" => "calendar",
            ],
            'P1D' => [
                "display_name" => $this->translate("Day"),
                "href_title" => $this->translate("Show performance data for the last day"),
                "href_icon" => "calendar",
            ],
            'P7D' => [
                "display_name" => $this->translate("Week"),
                "href_title" => $this->translate("Show performance data for the last week"),
                "href_icon" => "calendar",
            ],
            'P30D' => [
                "display_name" => $this->translate("Month"),
                "href_title" => $this->translate("Show performance data for the last month"),
                "href_icon" => "calendar",
            ]
        ];
    }

    /**
     * Implement the BaseHtmlElement assemble method.
     */
    protected function assemble(): void
    {
        foreach ($this->timeranges as $timerange => $details) {
            $elem = Html::tag(
                'a',
                [
                    'href' => $this->baseURL->overwriteParams([$this->rangeURLParam => $timerange])->getAbsoluteUrl(),
                    'class' => 'action-link',
                    'title' => $this->translate($details['href_title']),
                ],
                [ new Icon($details['href_icon'] ?? 'calender'), $this->translate($details['display_name']) ]
            );

            $this->add(Html::tag('li', $elem));
        }
    }
}
