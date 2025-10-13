<?php

namespace Icinga\Module\Perfdatagraphs\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\I18n\Translation;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

/**
 * The QuickActions adds links for selecting the duration.
 */
class QuickActions extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'quick-actions'];

    protected $defaultCurrentRange;

    // The URL parameter we append to the URL to specify the duration the backend should fetch
    protected $rangeURLParam = 'perfdatagraphs.duration';

    /**
     * @param string $defaultCurrentRange Value for the "Current" time range button
     * @param string $baseURL URL to use as base for the links. We get this from the request
     * so that we can support IcingaDB and the monitoring module
     */
    public function __construct($baseURL, string $defaultCurrentRange = 'PT12H')
    {
        $this->defaultCurrentRange = $defaultCurrentRange;
        $this->baseURL = $baseURL;
    }

    /**
     * Implement the BaseHtmlElement assemble method.
     */
    protected function assemble(): void
    {
        $current = Html::tag(
            'a',
            [
                'href' => $this->baseURL->overwriteParams([$this->rangeURLParam => $this->defaultCurrentRange])->getAbsoluteUrl(),
                'class' => 'action-link',
                'title' => $this->translate('Show the current performance data'),
            ],
            [ new Icon('calendar'), $this->translate('Current') ]
        );

        $this->add(Html::tag('li', $current));

        $day = Html::tag(
            'a',
            [
                'href' => $this->baseURL->overwriteParams([$this->rangeURLParam => 'P1D'])->getAbsoluteUrl(),
                'class' => 'action-link',
                'title' => $this->translate('Show performance data for the last day'),
            ],
            [ new Icon('calendar'), $this->translate('Day') ]
        );

        $week = Html::tag(
            'a',
            [
                'href' => $this->baseURL->overwriteParams([$this->rangeURLParam => 'P7D'])->getAbsoluteUrl(),
                'class' => 'action-link',
                'title' => $this->translate('Show performance data for the last week'),
            ],
            [ new Icon('calendar'), $this->translate('Week') ]
        );

        $month = Html::tag(
            'a',
            [
                'href' => $this->baseURL->overwriteParams([$this->rangeURLParam => 'P31D'])->getAbsoluteUrl(),
                'class' => 'action-link',
                'title' => $this->translate('Show performance data for the last month'),
            ],
            [ new Icon('calendar'), $this->translate('Month') ]
        );

        $this->add(Html::tag('li', $day));
        $this->add(Html::tag('li', $week));
        $this->add(Html::tag('li', $month));
    }
}
