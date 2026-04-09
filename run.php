<?php

/** @var $this \Icinga\Application\Modules\Module */

$this->provideHook('icingadb/IcingadbSupport');
$this->provideHook('icingadb/ServiceDetailExtension');
$this->provideHook('icingadb/HostDetailExtension');
$this->provideHook('monitoring/DetailviewExtension');
