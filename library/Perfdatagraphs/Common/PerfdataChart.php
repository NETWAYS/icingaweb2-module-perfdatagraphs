<?php

namespace Icinga\Module\Perfdatagraphs\Common;

use Icinga\Module\Perfdatagraphs\Widget\QuickActions;

use Icinga\Util\Json;

use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

/**
 * PerfdataChart contains common functionality used for rendering the performance data charts.
 * The idea is that you use this in the hook to create the chart elements.
 */
trait PerfdataChart
{
    use Translation;
    use PerfdataSource;


    /**
     * @param string $hostName Name of the host
     * @param string $serviceName Name of the service
     * @param string $checkcommandName Name of the checkcommand
     * @return string A valid HTML ID
     */
    private function generateID(string $hostName, string $serviceName, string $checkCommandName): string
    {
        $result = sprintf('%s-%s-%s', $hostName, $serviceName, $checkCommandName);

        $replace = [
            '/\s+/' => '_',
        ];

        return preg_replace(
            array_keys($replace),
            array_values($replace),
            trim($result)
        );
    }

    /**
     * createChart creates HTMLElements that are used to render charts in.
     *
     * @param string $hostName Name of the host
     * @param string $serviceName Name of the service
     * @param string $checkcommandName Name of the checkcommand
     * @param bool $isHostCheck Is this a Host check
     *
     * @return ValidHtml
     */
    public function createChart(string $hostName, string $serviceName, string $checkCommandName, bool $isHostCheck): ValidHtml
    {
        // Generic container for all elements we want to create here.
        $main = HtmlElement::create('div', ['class' => 'perfdata-charts']);

        // Ok so hear me out, since we are using a <canvas> to render the charts
        // we cannot use CSS classes to style the content of the chart.
        // However, we can use jQuery's .css() method to get CSS values from HTML elements,
        // which means we can create some non-visible elements with the style we want and
        // then fetch this data via JavaScript. Stupid? Maybe. Does it work? Yes.
        $colorClasses = ['axes-color', 'value-color', 'warning-color', 'critical-color'];
        foreach ($colorClasses as $class) {
            $d = HtmlElement::create('div', [
                'class' => $class,
            ]);
            $main->add($d);
        }

        // How we identify our elements in JS.
        $elemID = $this->generateID($hostName, $serviceName, $checkCommandName);

        // Where we store all elements for the charts.
        $charts = HtmlElement::create('div', [
            'class' => 'perfdata-charts-container collapsible',
            // Note: We could have a configuration option to change the
            // "always collapsed" behaviour
            'data-visible-height' => 0,
            'data-toggle-element' => '.perfdata-charts-container-control',
        ]);

        // We create our own collapsible control because we might
        // want to identify it in the JS
        $chartsControl = HtmlElement::create('div', [
            'class' => 'perfdata-charts-container-control',
            'id' => $elemID . '-control',
        ]);

        $b = new HtmlElement(
            'button',
            null,
            new Icon('angle-double-up', ['class' => 'collapse-icon']),
            new Icon('angle-double-down', ['class' => 'expand-icon'])
        );

        $chartsControl->add($b);

        // Add a headline and all other elements to our element.
        $header = Html::tag('h2', $this->translate('Performance Data Graph'));

        $main->add($header);

        // Load the module's configuration.
        $config = ModuleConfig::getConfig();

        $duration = $config['default_timerange'];

        // When there is a parameter for the duration we use that instead.
        if (Url::fromRequest()->hasParam('perfdatagraphs.duration')) {
            $duration = Url::fromRequest()->getParam('perfdatagraphs.duration');
        }

        // Fetch the perfdata for a given object via the hook.
        $perfdata = $this->fetchDataViaHook($hostName, $serviceName, $checkCommandName, $duration, $isHostCheck);

        // Error handling, if this gets too long, we could move this to a method.
        if ($perfdata->isEmpty()) {
            $msg = $this->translate('No data received');
            $main->add(HtmlElement::create(
                'p',
                ['class' => 'line-chart-error preformatted'],
                $msg,
            ));
            return $main;
        }


        if ($perfdata->hasErrors()) {
            $msg = $this->translate('Error while fetching performance data: %s');
            $main->add(HtmlElement::create(
                'p',
                ['class' => 'line-chart-error preformatted'],
                sprintf($msg, join(' ', $perfdata->errors)),
            ));
            return $main;
        }

        if (!$perfdata->isValid()) {
            $msg = $this->translate('Invalid data received: %s');
            $main->add(HtmlElement::create(
                'p',
                ['class' => 'line-chart-error preformatted'],
                sprintf($msg, join(' ', $perfdata->errors)),
            ));
            return $main;
        }

        // Element in which the charts will get rendered.
        // We use attributes on this elements to transport data
        // to the JavaScript part of this module.
        $chart = HtmlElement::create('div', [
            'id' => $elemID,
            'class' => 'line-chart',
            'data-perfdata' => Json::sanitize($perfdata),
        ]);

        $charts->add((new QuickActions(Url::fromRequest(), $config['default_timerange'])));
        $charts->add($chart);
        $main->add($charts);
        $main->add($chartsControl);

        return $main;
    }
}
