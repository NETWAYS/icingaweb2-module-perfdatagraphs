<?php

namespace Icinga\Module\Perfdatagraphs\ProvidedHook\Icingadb;

use Icinga\Module\Perfdatagraphs\Common\ModuleConfig;
use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\Common\PerfdataChart;
use Icinga\Module\Perfdatagraphs\Common\PerfdataSource;
use Icinga\Module\Perfdatagraphs\Icingadb\IcingaObjectHelper;

use Icinga\Module\Icingadb\Hook\ServiceDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Service;

use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

/**
 * ServiceDetailExtension adds the Chart HTML for Service objects.
 */
class ServiceDetailExtension extends ServiceDetailExtensionHook
{
    use PerfdataChart;

    /**
     * getHtmlForObject returns the Chart HTML.
     */
    public function getHtmlForObject(Service $service): ValidHtml
    {
        $serviceName = $service->name ?? '';
        $hostName = $service->host->name ?? '';
        $checkCommandName = $service->checkcommand_name ?? '';

        $cvh = new IcingaObjectHelper();
        $customvars = $cvh->getPerfdataGraphsConfigForObject($service);

        // Check if charts are disabled for this object, if so we just return.
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_DISABLE] ?? false) {
            return Html::tag('div');
        }

        $isHostCheck = false;

        // If the object wants the data from a custom backend
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND] ?? false) {
            $hook = ModuleConfig::getHookByName($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND]);
        } else {
            $hook = ModuleConfig::getHook();
        }
        // If there is no hook configured we return here.
        if (empty($hook)) {
            $err = Html::tag('div');
            $err->add(HtmlElement::create('p', ['class' => 'line-chart-error preformatted'], $this->translate('No hook configured.')));
            return $err;
        }

        $metricsToInclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_INCLUDE] ?? false) {
            $metricsToInclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_INCLUDE];
        }

        $metricsToExclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE] ?? false) {
            $metricsToExclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE];
        }

        // Load the module's configuration.
        $config = ModuleConfig::getConfigWithDefaults();
        $duration = $config['default_timerange'];
        // When there is a parameter for the duration we use that instead.
        if (Url::fromRequest()->hasParam('perfdatagraphs.duration')) {
            $duration = Url::fromRequest()->getParam('perfdatagraphs.duration');
        }

        $source = new PerfdataSource($config, $hook);
        $request = new PerfdataRequest($hostName, $serviceName, $checkCommandName, $duration, $isHostCheck, $metricsToInclude, $metricsToExclude);

        $customVarsMetrics = $cvh->getPerfdataGraphsMetricsForObject($service);

        $response = $source->fetch($request, $customVarsMetrics);

        // If the a dataset is set to be highlighted, move it at the top of the array.
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_HIGHLIGHT] ?? false) {
            $response->setDatasetToHighlight($customvars[$cvh::CUSTOM_VAR_CONFIG_HIGHLIGHT] ?? '');
        }

        // Get the configured element for the host.
        $chart = $this->createChart($request, $response);

        if (empty($chart)) {
            $err = Html::tag('div');
            $err->add(HtmlElement::create('p', ['class' => 'line-chart-error preformatted'], $this->translate('Chart could be rendered.')));
            return $err;
        }

        $link = new Link(
            $this->translate('Show all performance data graphs'),
            Url::fromPath('perfdatagraphs/graph')->addParams([
                'host' => $hostName,
                'service' => $serviceName,
                'checkcommand' => $checkCommandName,
                'ishostcheck' => 'false'
            ]),
        );

        $d = Html::tag('div');
        $d->add($chart);
        $d->add($link);

        return $d;
    }
}
