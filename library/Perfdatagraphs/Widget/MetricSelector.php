<?php

namespace Icinga\Module\Perfdatagraphs\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\I18n\Translation;
use ipl\Web\Url;

/**
 * MetricSelector renders a set of toggleable links — one per available
 * perfdata metric, so the user can pick which charts to display.
 * Each click adds/removes a perfdatagraphs.label URL param; IcingaWeb2
 * intercepts the anchor clicks and reloads the tab content via AJAX.
 */
class MetricSelector extends BaseHtmlElement
{
    use Translation;
    protected $tag = 'div';
    protected $defaultAttributes = ['class' => 'perfdatagraphs-metric-selector'];
    protected array $availableLabels;
    protected array $selectedLabels;
    protected Url $baseUrl;

    /**
     * @param string[] $availableLabels All metric labels returned by the backend
     * @param string[] $selectedLabels  Labels currently selected (from URL params)
     * @param Url      $baseUrl         Current request URL (used as link base)
     */
    public function __construct(array $availableLabels, array $selectedLabels, Url $baseUrl)
    {
        $this->availableLabels = $availableLabels;
        $this->selectedLabels  = $selectedLabels;
        $this->baseUrl         = $baseUrl;
    }

    protected function assemble(): void
    {
        if (empty($this->availableLabels)) {
            return;
        }

        // data-base-url lets the JS reconstruct URLs for range selections
        $this->addAttributes([
            'data-base-url' => $this->baseUrl->without('perfdatagraphs.label')->getAbsoluteUrl(),
        ]);

        $this->add(Html::tag(
            'p',
            ['class' => 'metric-selector-title'],
            $this->translate('Select metrics to display:')
        ));

        $list = Html::tag('ul', ['class' => 'metric-selector-list']);

        $index = 0;
        foreach ($this->availableLabels as $label) {
            $isSelected = in_array($label, $this->selectedLabels, true);

            $list->add(Html::tag('li', Html::tag('a', [
                'href'       => '#',
                'class'      => 'metric-label-link' . ($isSelected ? ' selected' : ''),
                'data-label' => $label,
                'data-index' => $index,
            ], $label)));
            $index++;
        }

        $this->add($list);
        $this->add(Html::tag('div', ['class' => 'metric-selector-actions'], [
            Html::tag('button', ['class' => 'metric-selector-apply', 'type' => 'button'],
                $this->translate('Apply')),
            Html::tag('button', ['class' => 'metric-selector-clear', 'type' => 'button'],
                $this->translate('Clear')),
        ]));
    }
}
