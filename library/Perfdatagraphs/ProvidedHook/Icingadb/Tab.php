<?php

namespace Icinga\Module\Perfdatagraphs\ProvidedHook\Icingadb;

use Icinga\Module\Perfdatagraphs\Common\ModuleConfig;
use Icinga\Module\Perfdatagraphs\Common\PerfdataChart;
use Icinga\Module\Perfdatagraphs\Common\PerfdataSource;
use Icinga\Module\Perfdatagraphs\Icingadb\IcingaObjectHelper;
use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;

use Icinga\Module\Icingadb\Hook\TabHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;

use Icinga\Application\Icinga;

use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Orm\Model;

class Tab extends TabHook
{
    use PerfdataChart;

    public function getName(): string
    {
        return 'graphs';
    }

    public function getLabel(): string
    {
        return t('Performance Data Graph');
    }

    protected function addError(string $message): HtmlElement
    {
        $err = Html::tag('div');
        $err->add(HtmlElement::create('p', ['class' => 'line-chart-error preformatted'], $message));
        return $err;
    }

    public function getContent(Model $object): array
    {
        $isHostCheck = false;
        if ($object instanceof Host) {
            $serviceName = $object->checkcommand_name;
            $isHostCheck = true;
            $checkcommandName = $object->checkcommand_name;
            $hostName = $object->name;
        } elseif ($object instanceof Service) {
            $serviceName = $object->name;
            $checkcommandName = $object->checkcommand_name;
            $hostName = $object->host->name;
        }

        $request = Icinga::app()->getRequest();

        $config = ModuleConfig::getConfigWithDefaults();
        $defaultDuration = $config['default_timerange'];

        // Retrieve the URL parameters.
        $duration = $request->getParam('perfdatagraphs_duration', $defaultDuration);

        // Optional list of labels, when passed only the given perfdata metrics will be shown
        $labels = $request->getParam('labels', []);

        $cvh = new IcingaObjectHelper();

        $customvars = $cvh->getPerfdataGraphsConfigForObject($object);

        // If the object wants the data from a custom backend
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND] ?? false) {
            $hook = ModuleConfig::getHookByName($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND]);
        } else {
            $hook = ModuleConfig::getHook();
        }

        // If there is no hook configured we return here.
        $content = [];
        if (empty($hook)) {
            $content[] = $this->addError($this->translate('No hook configured'));
            return $content;
        }

        $source = new PerfdataSource($config, $hook);
        $request = new PerfdataRequest($hostName, $serviceName, $checkcommandName, $duration, $isHostCheck, [], []);

        $customVarsMetrics = $cvh->getPerfdataGraphsMetricsForObject($object);

        $response = $source->fetch($request, $customVarsMetrics);

        $limit = -1;
        $chart = $this->createChart(request: $request, response: $response, filter: $labels, limit: $limit);
        $content[] = HtmlString::create($chart);

        if (empty($chart)) {
            $content[] = $this->addError($this->translate('Chart could not be rendered'));
            return $content;
        }

        return $content;
    }
}
