<?php

namespace Icinga\Module\Perfdatagraphs\ProvidedHook\Monitoring;

use Icinga\Module\Perfdatagraphs\Common\ModuleConfig;
use Icinga\Module\Perfdatagraphs\Common\PerfdataChart;
use Icinga\Module\Perfdatagraphs\Common\PerfdataSource;
use Icinga\Module\Perfdatagraphs\Ido\IcingaObjectHelper;
use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;

use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;

use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class DetailviewExtension extends DetailviewExtensionHook
{
    use PerfdataChart;

    public function getHtmlForObject(MonitoredObject $object)
    {
        $isHostCheck = false;

        if ($object instanceof Host) {
            $serviceName = $object->host_check_command;
            $hostName = $object->getName();
            $checkCommandName = $object->host_check_command;
            $isHostCheck = true;
        } elseif ($object instanceof Service) {
            $serviceName = $object->getName();
            $hostName = $object->getHost()->getName();
            $checkCommandName = $object->check_command;
        } else {
            // Unecessary but just to be safe.
            return Html::tag('div');
        }

        $cvh = new IcingaObjectHelper();
        $customvars = $cvh->getPerfdataGraphsConfigForObject($object);

        // Check if charts are disabled for this object, if so we just return.
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_DISABLE] ?? false) {
            return Html::tag('div');
        }

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

        // Load the module's configuration.
        $config = ModuleConfig::getConfigWithDefaults();
        $duration = $config['default_timerange'];
        // When there is a parameter for the duration we use that instead.
        if (Url::fromRequest()->hasParam('perfdatagraphs.duration')) {
            $duration = Url::fromRequest()->getParam('perfdatagraphs.duration');
        }

        $metricsToInclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_INCLUDE] ?? false) {
            $metricsToInclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_INCLUDE];
        }

        $metricsToExclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE] ?? false) {
            $metricsToExclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE];
        }

        $source = new PerfdataSource($config, $hook);
        $request = new PerfdataRequest($hostName, $serviceName, $checkCommandName, $duration, $isHostCheck, $metricsToInclude, $metricsToExclude);

        $customVarsMetrics = $cvh->getPerfdataGraphsMetricsForObject($object);

        $response = $source->fetch($request, $customVarsMetrics);

        // If the a dataset is set to be highlighted, move it at the top of the array.
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_HIGHLIGHT] ?? false) {
            $response->setDatasetToHighlight($customvars[$cvh::CUSTOM_VAR_CONFIG_HIGHLIGHT] ?? '');
        }

        $limit = (count($metricsToInclude) > 0 || count($metricsToExclude) > 0) ? -1 : 3;
        $chart = $this->createChart($request, $response, $limit);

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
