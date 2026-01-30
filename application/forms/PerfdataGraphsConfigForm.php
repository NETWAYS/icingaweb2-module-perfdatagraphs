<?php

namespace Icinga\Module\Perfdatagraphs\Forms;

use Icinga\Application\Hook;
use Icinga\Forms\ConfigForm;

use DateInterval;
use Exception;
use Zend_Validate_Callback;

/**
 * PerfdataGraphsConfigForm represents the configuration form for the PerfdataGraphs Module.
 */
class PerfdataGraphsConfigForm extends ConfigForm
{
    /**
     * Initialize the form
     */
    public function init()
    {
        $this->setName('form_config_perfdatagraphs');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * listBackends returns a list of all available PerfdataSource hooks.
     */
    protected function listBackends(): array
    {
        $hooks = Hook::all('perfdatagraphs/PerfdataSource');

        $enum = array();
        foreach ($hooks as $hook) {
            $enum[mb_strtolower($hook->getName())] = $hook->getName();
        }
        asort($enum);

        return $enum;
    }

    /**
     * assemble the configuration form with all available options.
     */
    public function createElements(array $formData)
    {
        $callbackValidator = new Zend_Validate_Callback(function ($value) {
            try {
                $int = new DateInterval($value);
            } catch (Exception $e) {
                return false;
            }
            return true;
        });

        $callbackValidator->setMessage(
            $this->translate('Invalid time range. Use the ISO8601 duration format (e.g. PT2H, P1D)'),
            Zend_Validate_Callback::INVALID_VALUE
        );

        $this->addElement('text', 'perfdatagraphs_default_timerange', [
            'description' => t('Default time range for the "Current" button. Uses the ISO8601 duration format (e.g. PT2H, P1D). Hint: too small a value may result in invalid data'),
            'label' => 'Default Time Range (ISO8601 duration)',
            'placeholder' => 'PT12H',
            'validators' => [
                $callbackValidator
            ],
        ]);

        $this->addElement('number', 'perfdatagraphs_cache_lifetime', [
            'label' => t('Cache lifetime in seconds'),
            'description' => t('How long the data for the charts will be cached by the server.'),
            'placeholder' => 900,
        ]);

        $backends = $this->listBackends();
        $choose = ['' => sprintf(' - %s - ', t('Please choose'))];

        $this->addElement(
            'select',
            'perfdatagraphs_default_backend',
            [
                'label' => $this->translate('Default Data Backend'),
                'description' => $this->translate('Default backend for the performance data graphs. With only one backend is installed, it will be used by default.'),
                'multiOptions' => array_merge($choose, $backends),
                'class' => 'autosubmit',
            ]
        );
    }
}
