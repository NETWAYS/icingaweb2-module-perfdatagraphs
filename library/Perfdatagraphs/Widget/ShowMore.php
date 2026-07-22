<?php

namespace Icinga\Module\Perfdatagraphs\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class ShowMore extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'show-more'];
    protected $tag = 'div';
    protected Url $url;
    protected string $label;
    protected array $attrs;

    public function __construct(Url $url, string $label, array $attrs = [])
    {
        $this->url = $url;
        $this->label = $label;
        $this->attrs = $attrs;
    }

    protected function assemble(): void
    {
        $this->addHtml(new ActionLink(content: $this->label, url: $this->url, attributes: $this->attrs));
    }
}
