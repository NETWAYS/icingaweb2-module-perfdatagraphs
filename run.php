<?php

/** @var $this \Icinga\Application\Modules\Module */

$section = $this->menuSection(N_('Graphite'), ['icon' => 'chart-area']);
$this->provideHook('icingadb/IcingadbSupport');
$this->provideHook('icingadb/ServiceDetailExtension');
$this->provideHook('icingadb/HostDetailExtension');

$this->provideHook('monitoring/DetailviewExtension');
$this->provideHook('monitoring/DetailviewExtension');
