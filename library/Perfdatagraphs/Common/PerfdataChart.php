<?php

namespace Icinga\Module\Perfdatagraphs\Common;

use Icinga\Module\Perfdatagraphs\Widget\QuickActions;
use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\Model\PerfdataResponse;

use Icinga\Application\Benchmark;
use Icinga\Application\Logger;
use Icinga\Util\Json;

use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Url;

/**
 * PerfdataChart contains common functionality used for rendering the performance data charts.
 * The idea is that you use this to create the chart elements.
 */
trait PerfdataChart
{
    use Translation;

    /**
     * generateID generate a unique and safe ID for each chart.
     * @param string $hostName Name of the host
     * @param string $serviceName Name of the service
     * @param string $checkcommandName Name of the checkcommand
     * @return string A valid HTML ID
     */
    private function generateID(string $hostName, string $serviceName, string $checkCommandName): string
    {
        // Since there might be whatever in the names.
        return rtrim(base64_encode(sprintf('%s-%s-%s', $hostName, $serviceName, $checkCommandName)), '=');
    }

    /**
     * createChart creates HTMLElements that are used to render charts in.
     *
     * @param PerfdataRequest $request We need the request because it contains names of host/service
     * @param PerfdataResponse $response We need the response because where else would the data be?
     * @param array $filter Only show graphs with these labels
     * @param int $limit How many charts to render, -1 will render all charts
     * @return ValidHtml
     */
    public function createChart(PerfdataRequest $request, PerfdataResponse $response, array $filter = [], int $limit = 3): ValidHtml
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
        $elemID = $this->generateID($request->getHostname(), $request->getServicename(), $request->getCheckcommand());

        // Where we store all elements for the charts.
        $charts = HtmlElement::create('div', ['class' => 'perfdata-charts-container', 'id' => $elemID]);

        // We create our own collapsible control because we might want to identify it in the JS
        $chartsControl = HtmlElement::create('div', ['class' => 'perfdata-charts-container-control', 'id' => $elemID . '-control']);

        // Add a headline and all other elements to our element.
        $header = Html::tag('h2', $this->translate('Performance Data Graph'));

        $main->add($header);

        // Load the module's configuration.
        $config = ModuleConfig::getConfigWithDefaults();

        $duration = $config['default_timerange'];

        // When there is a parameter for the duration we use that instead.
        if (Url::fromRequest()->hasParam('perfdatagraphs.duration')) {
            $duration = Url::fromRequest()->getParam('perfdatagraphs.duration');
        }

        Benchmark::measure('Rendering performance data elements');

        $main->add((new QuickActions(Url::fromRequest())));

        $errorMsg = null;

        // Error handling, if this gets too long, we could move this to a method.
        if ($response->hasErrors()) {
            $errorMsg = sprintf($this->translate('Error while fetching data: %s'), join(' ', $response->getErrors()));
            Logger::debug('Error while fetching data: %s', Json::sanitize($response));
        }

        if ($response->isEmpty()) {
            $errorMsg = $errorMsg . ' ' . $this->translate('No data received.');
        }

        if (!$response->isValid()) {
            $errorMsg = $errorMsg . ' ' . sprintf($this->translate('Invalid data received: %s'), join(' ', $response->getErrors()));
            Logger::debug('Invalid data received: %s', Json::sanitize($response));
        }

        if (isset($errorMsg)) {
            $main->add(HtmlElement::create('p', ['class' => 'line-chart-error preformatted'], $errorMsg));
            return $main;
        }

        $datasets = [];
        foreach ($response->getDatasets() as $dataset) {
            // If the filter param is set, we only use the dataset when the label matches
            if (count($filter) > 0) {
                if (!in_array($dataset->getTitle(), $filter)) {
                    continue;
                }
            }
            $datasets[$dataset->getTitle()] = Json::sanitize($dataset);
        }

        // We only render the first three (magic number has no meaning) charts unless include/exclude is set
        $count = 0;

        // Elements in which the charts will get rendered.
        // We use attributes on this elements to transport data
        // to the JavaScript part of this module
        foreach ($datasets as $title => $data) {
            if ($limit != -1 && $count >= $limit) {
                break;
            }
            $chart = HtmlElement::create('div', [
                // We use a perfdatagraphs prefix here to avoid overlap with other modules (i.e. Icinga Kubernetes)
                'class' => 'perfdatagraphs-line-chart',
                'id' => $elemID . '_' . $title,
                'data-perfdata' => $data,
            ]);
            $charts->add($chart);
            $count++;
        }

        $main->add($charts);

        $main->add($chartsControl);

        Benchmark::measure('Rendered performance data elements');

        return $main;
    }
}
