<?php

namespace Icinga\Module\Perfdatagraphs\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Link;

class TagList extends BaseHtmlElement
{
    protected $content = [];
    protected $defaultAttributes = ['class' => 'tag-list'];
    protected $tag = 'div';

    public function addLink($content, $url, $attrs): self
    {
        $this->content[] = new Link($content, $url, $attrs);

        return $this;
    }

    public function hasContent(): bool
    {
        return ! empty($this->content);
    }

    protected function assemble()
    {
        $this->add(Html::wrapEach($this->content, 'li'));
    }
}
