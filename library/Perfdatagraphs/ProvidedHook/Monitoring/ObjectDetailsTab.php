<?php

namespace Icinga\Module\PerfdataGraphs\ProvidedHook\Monitoring;

use Icinga\Module\Perfdatagraphs\Common\ModuleConfig;
use Icinga\Module\Perfdatagraphs\Common\PerfdataChart;
use Icinga\Module\Perfdatagraphs\Common\PerfdataSource;
use Icinga\Module\Perfdatagraphs\Ido\IcingaObjectHelper as IdoCVH;
use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\Widget\TagList;

use Icinga\Module\Monitoring\Hook\ObjectDetailsTabHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;

use Icinga\Application\Icinga;
use Icinga\Web\Request;

use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

class ObjectDetailsTab extends ObjectDetailsTabHook
{
    use PerfdataChart;

    public function getName()
    {
        return 'graphs';
    }

    public function getLabel()
    {
        return 'Performance Data Graph';
    }

    protected function addError(string $message): HtmlElement
    {
        $err = Html::tag('div');
        $err->add(HtmlElement::create('p', ['class' => 'line-chart-error preformatted'], $message));
        return $err;
    }

    public function getContent(MonitoredObject $object, Request $request)
    {
        $isHostCheck = false;

        if ($object instanceof Host) {
            $serviceName = $object->host_check_command;
            $hostName = $object->getName();
            $checkCommandName = $object->host_check_command;
            $checkInterval = intval($object->host_check_interval);
            $isHostCheck = true;
        } elseif ($object instanceof Service) {
            $serviceName = $object->getName();
            $hostName = $object->getHost()->getName();
            $checkCommandName = $object->check_command;
            $checkInterval = intval($object->service_check_interval);
        } else {
            return Html::tag('div');
        }

        $config = ModuleConfig::getConfigWithDefaults();
        $defaultDuration = $config['default_timerange'];
        // Retrieve the URL parameters.
        $duration = $request->getParam('perfdatagraphs_duration', $defaultDuration);

        // Optional list of labels, when passed only the given perfdata metrics will be shown
        $labels = $request->getUrl()->getParams()->getValues('perfdatagraphs.label');

        $cvh = new IdoCVH();

        $view = Icinga::app()->getViewRenderer()->view;
        $customvars = $cvh->getPerfdataGraphsConfigForObject($object);

        // If the object wants the data from a custom backend
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND] ?? false) {
            $hook = ModuleConfig::getHookByName($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND]);
        } else {
            $hook = ModuleConfig::getHook();
        }

        // If there is no hook configured we return here.
        if (empty($hook)) {
            return $this->addError($this->translate('No hook configured'));
        }

        $metricsToExclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE] ?? false) {
            $metricsToExclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE];
        }

        $source = new PerfdataSource($config, $hook);
        $perfdatarequest = new PerfdataRequest(
            hostName: $hostName,
            serviceName: $serviceName,
            checkCommand: $checkCommandName,
            checkInterval: $checkInterval,
            duration: $duration,
            isHostCheck: $isHostCheck,
            includeMetrics: [],
            excludeMetrics: $metricsToExclude
        );

        $customVarsMetrics = $cvh->getPerfdataGraphsMetricsForObject($object);

        $response = $source->fetch($perfdatarequest, $customVarsMetrics);

        // Ensure labels have a predictable order
        $sets = $response->getDatasets();
        uasort($sets, function ($a, $b) {
            return strnatcmp($a->getTitle(), $b->getTitle());
        });

        $collapsible = $this->createTagList($sets, $labels);

        $content = [];

        // We hide the label list in the compact view, e.g. Dashboards
        if (!$view->compact) {
            $content[] = $collapsible;
        }
        $response->setDatasets($sets);

        $limit = -1;
        $chart = $this->createChart(request: $perfdatarequest, response: $response, filter: $labels, limit: $limit);

        if (empty($chart)) {
            return $this->addError($this->translate('Chart could not be rendered'));
        }
        $content[] = $chart;

        return Html::tag('div', ['class' => 'icinga-module module-perfdatagraphs'], $content);
    }

    private function createTagList($sets, $labels): HtmlElement
    {
        $labelList = new TagList();
        $collapsible = new HtmlElement(
            'div',
            new Attributes(['class' => 'collapsible', 'data-visible-height' => 65, 'label' => 'Collapse']),
        );

        // Button to remove all selected labels
        $labelList->addLink($this->translate('Remove selection'), Url::fromRequest()->without('perfdatagraphs.label'), ['title' => $this->translate('Deselect all selected metrics')]);

        foreach ($sets as $set) {
            $t = $set->getTitle();
            $isActive = in_array($t, $labels);
            $attrs = $isActive ? ['class' => 'active'] : ['class' => 'inactive'];
            $attrs['title'] = sprintf('Toggle the %s metric', $t);
            // Build new label list supporting multiple identical query keys
            if ($isActive) {
                // Remove current label
                $newLabels = array_filter($labels, fn($v) => $v !== $t);
                $newLabels = array_values($newLabels);
            } else {
                // Add current label
                $newLabels = $labels;
                $newLabels[] = $t;
            }
            // Start from the current request but remove any existing label parameters
            $url = Url::fromRequest()->without('perfdatagraphs.label');
            foreach ($newLabels as $lbl) {
                // add each active label to the url
                $url->addFilter(\Icinga\Data\Filter\Filter::where('perfdatagraphs.label', $lbl));
            }
            $labelList->addLink($t, $url, $attrs);
        }
        $collapsible->add($labelList);

        return $collapsible;
    }
}
