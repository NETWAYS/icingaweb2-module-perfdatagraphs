<?php

namespace Icinga\Module\Perfdatagraphs\ProvidedHook\Icingadb;

use Icinga\Module\Perfdatagraphs\Common\ModuleConfig;
use Icinga\Module\Perfdatagraphs\Common\PerfdataChart;
use Icinga\Module\Perfdatagraphs\Common\PerfdataSource;
use Icinga\Module\Perfdatagraphs\Icingadb\IcingaObjectHelper;
use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\Widget\TagList;

use Icinga\Module\Icingadb\Hook\TabHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;

use Icinga\Application\Icinga;
use Icinga\Data\Filter\Filter;

use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Orm\Model;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

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
            $checkCommandName = $object->checkcommand_name;
            $checkInterval = intval($object->check_interval);
            $hostName = $object->name;
        } elseif ($object instanceof Service) {
            $serviceName = $object->name;
            $checkCommandName = $object->checkcommand_name;
            $checkInterval = intval($object->check_interval);
            $hostName = $object->host->name;
        } else {
            return [];
        }

        $request = Icinga::app()->getRequest();
        $view = Icinga::app()->getViewRenderer()->view;

        $config = ModuleConfig::getConfigWithDefaults();
        $defaultDuration = $config['default_timerange'];

        // Retrieve the URL parameters.
        $duration = $request->getParam('perfdatagraphs_duration', $defaultDuration);

        // Optional list of labels, when passed only the given perfdata metrics will be shown
        $labels = $request->getUrl()->getParams()->getValues('perfdatagraphs.label');

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

        $metricsToExclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE] ?? false) {
            $metricsToExclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE];
        }

        $source = new PerfdataSource($config, $hook);
        $perfRequest = new PerfdataRequest(
            hostName: $hostName,
            serviceName: $serviceName,
            checkCommand: $checkCommandName,
            checkInterval: $checkInterval,
            duration: $duration,
            isHostCheck: $isHostCheck,
            includeMetrics: [],
            excludeMetrics: $metricsToExclude,
        );

        $customVarsMetrics = $cvh->getPerfdataGraphsMetricsForObject($object);

        $response = $source->fetch($perfRequest, $customVarsMetrics);

        // Ensure labels have a predictable order
        $sets = $response->getDatasets();
        uasort($sets, function ($a, $b) {
            return strnatcmp($a->getTitle(), $b->getTitle());
        });

        $response->setDatasets($sets);

        $sets = $response->getDatasets();
        $collapsible = $this->createTagList($sets, $labels);

        // We hide the label list in the compact view, e.g. Dashboards
        if (!$view->compact) {
            $content[] = $collapsible;
        }

        $limit = -1;
        $chart = $this->createChart(request: $perfRequest, response: $response, filter: $labels, limit: $limit);
        $content[] = HtmlString::create($chart);

        if (empty($chart)) {
            $content[] = $this->addError($this->translate('Chart could not be rendered'));
            return $content;
        }

        return $content;
    }

    private function createTagList($sets, $labels): HtmlElement
    {
        $labelList = new TagList();
        $collapsible = new HtmlElement(
            'div',
            new Attributes(['class' => 'collapsible', 'data-visible-height' => 65, 'label' => 'Collapse']),
        );

        // Button to remove all selected labels
        $labelList->addLink(
            $this->translate('Deselect All'),
            Url::fromRequest()->without('perfdatagraphs.label'),
            [
                'title' => $this->translate('Deselect all selected metrics'),
                'class' => 'action-link'
            ]
        );

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
                $url->addFilter(Filter::where('perfdatagraphs.label', $lbl));
            }
            $labelList->addLink($t, $url, $attrs);
        }
        $collapsible->add($labelList);

        return $collapsible;
    }
}
