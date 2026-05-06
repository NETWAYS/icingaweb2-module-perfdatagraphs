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

        $this->add(Html::tag(
            'p',
            ['class' => 'metric-selector-title'],
            $this->translate('Select metrics to display:')
        ));

        $list = Html::tag('ul', ['class' => 'metric-selector-list']);

        foreach ($this->availableLabels as $label) {
            $isSelected = in_array($label, $this->selectedLabels, true);

            // Build the new set of selected labels after this toggle
            if ($isSelected) {
                $newLabels = array_values(array_filter(
                    $this->selectedLabels,
                    fn($l) => $l !== $label
                ));
            } else {
                $newLabels = array_merge($this->selectedLabels, [$label]);
            }

            // Start from the base URL with all existing label params stripped
            $urlStr = $this->baseUrl->without('perfdatagraphs.label')->getAbsoluteUrl();

            // Append each desired label as a separate query param
            if (!empty($newLabels)) {
                $separator  = strpos($urlStr, '?') !== false ? '&' : '?';
                $queryParts = array_map(
                    fn($l) => 'perfdatagraphs.label=' . rawurlencode($l),
                    $newLabels
                );
                $urlStr .= $separator . implode('&', $queryParts);
            }

            $list->add(Html::tag('li', Html::tag('a', [
                'href'  => $urlStr,
                'class' => 'metric-label-link' . ($isSelected ? ' selected' : ''),
            ], $label)));
        }

        $this->add($list);
    }
}
