<?php

namespace Icinga\Module\Perfdatagraphs\Controllers;

use Icinga\Module\Perfdatagraphs\Common\ModuleConfig;
use Icinga\Module\Perfdatagraphs\Common\PerfdataChart;
use Icinga\Module\Perfdatagraphs\Common\PerfdataSource;
use Icinga\Module\Perfdatagraphs\Icingadb\IcingaObjectHelper as IcinaDBCVH;
use Icinga\Module\Perfdatagraphs\Ido\IcingaObjectHelper as IdoCVH;
use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\ProvidedHook\Icingadb\IcingadbSupport;

use Icinga\Application\Modules\Module;
use Icinga\Application\Logger;

use ipl\Html\HtmlString;
use ipl\Html\HtmlElement;
use ipl\Html\Html;
use ipl\Web\Compat\CompatController;

/**
 * GraphController shows all performance data charts for a given Host/Service
 */
class GraphController extends CompatController
{
    use PerfdataChart;

    protected bool $disableDefaultAutoRefresh = true;

    /**
     * Initialize the Controller.
     */
    public function init(): void
    {
        // Assert the user has access to this controller.
        $this->assertPermission('perfdatagraphs/view');
        parent::init();
    }

    public function indexAction(): void
    {
        $this->getTabs()
            ->add('graph', [
                'label' => 'Graph',
                'url' => 'perfdatagraphs/graph'
            ])
            ->activate('graph');

        // Load the module's configuration.
        $config = ModuleConfig::getConfigWithDefaults();
        $defaultDuration = $config['default_timerange'];
        $duration = $this->params->get('perfdatagraphs.duration', $defaultDuration);

        // Retrieve the URL parameters.
        $hostName = $this->params->getRequired('host');
        $serviceName = $this->params->getRequired('service');
        $checkcommandName = $this->params->getRequired('checkcommand');
        $isHostCheck = $this->params->getRequired('ishostcheck');

        // Transform the URL param into a boolean just because it is easier to work with
        $isHostCheck = $isHostCheck === 'true' ? true : false;

        if (Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
            Logger::debug('Used IcingaDB as database backend');
            $cvh = new IcinaDBCVH();
        } else {
            Logger::debug('Used IDO as database backend');
            $cvh = new IdoCVH();
        }

        // Get the object so that we can get its custom variables.
        $object = $cvh->getObjectFromString($hostName, $serviceName, $isHostCheck);

        if (empty($object)) {
            $err = Html::tag('div');
            $err->add(HtmlElement::create('p', ['class' => 'line-chart-error preformatted'], $this->translate('Failed to find object from given host-service strings')));
            $this->addContent($err);
            return;
        }

        $customvars = $cvh->getPerfdataGraphsConfigForObject($object);

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
            $this->addContent($err);
            return;
        }

        $source = new PerfdataSource($config, $hook);
        $request = new PerfdataRequest($hostName, $serviceName, $checkcommandName, $duration, $isHostCheck, [], []);

        $customVarsMetrics = $cvh->getPerfdataGraphsMetricsForObject($object);

        $response = $source->fetch($request, $customVarsMetrics);

        // Get the configured element for the host.
        $chart = $this->createChart($request, $response, -1);

        if (empty($chart)) {
            $err = Html::tag('div');
            $err->add(HtmlElement::create('p', ['class' => 'line-chart-error preformatted'], $this->translate('Chart could be rendered.')));
            $this->addContent($err);
            return;
        }

        $this->addContent(HtmlString::create($chart));
    }
}
