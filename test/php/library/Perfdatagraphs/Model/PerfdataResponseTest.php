<?php

namespace Tests\Icinga\Module\Perfdatagraphs;

use Icinga\Module\Perfdatagraphs\Model\PerfdataResponse;
use Icinga\Module\Perfdatagraphs\Model\PerfdataSet;
use Icinga\Module\Perfdatagraphs\Model\PerfdataSeries;

use SplFixedArray;

use PHPUnit\Framework\TestCase;

final class PerfdataResponseTest extends TestCase
{
    public function test_perfdataseries_isEmpty_WithSPL()
    {
        $actual = new PerfdataSeries('ps', [1,2]);
        $this->assertFalse($actual->isEmpty());

        $actual = new PerfdataSeries('ps', [1, null]);
        $this->assertFalse($actual->isEmpty());

        $actual = new PerfdataSeries('ps', []);
        $this->assertTrue($actual->isEmpty());

        $actual = new PerfdataSeries('ps', [null, null]);
        $this->assertTrue($actual->isEmpty());

        $values = new SplFixedArray(1);
        $actual = new PerfdataSeries('ps', $values);
        $this->assertTrue($actual->isEmpty());

        $values = new SplFixedArray(1);
        $values[0] = 10;
        $actual = new PerfdataSeries('ps', $values);
        $this->assertFalse($actual->isEmpty());
    }

    public function test_perfdataset_isEmpty_WithArray()
    {
        $actual = new PerfdataSet('myset', 'theunit');
        $s1 = new PerfdataSeries('foo', [1,2]);
        $actual->addSeries($s1);
        $this->assertFalse($actual->isEmpty());

        $actual = new PerfdataSet('myset', 'theunit');
        $this->assertTrue($actual->isEmpty());

        $actual = new PerfdataSet('myset', 'theunit');
        $s1 = new PerfdataSeries('foo', [null, null]);
        $actual->addSeries($s1);
        $this->assertTrue($actual->isEmpty());

        $values = new SplFixedArray(1);
        $actual = new PerfdataSet('myset', 'theunit');
        $s1 = new PerfdataSeries('ps', $values);
        $this->assertTrue($actual->isEmpty());
    }

    public function test_perfdataresponse()
    {
        $pfr = new PerfdataResponse();

        $this->assertTrue($pfr->isEmpty());

        $ds = new PerfdataSet('myset', 'theunit');

        $s1 = new PerfdataSeries('foo', [1,2]);
        $s2 = new PerfdataSeries('bar', [3,4]);

        $ds->addSeries($s1);
        $ds->addSeries($s2);

        $pfr->addDataset($ds);

        $this->assertEquals($ds, $pfr->getDataset('myset'));

        $pfr->addError('WRONG!');

        $expected = '{"errors":["WRONG!"],"data":[{"title":"myset","unit":"theunit","show_thresholds":true,"timestamps":[],"series":[{"name":"foo","values":[1,2]},{"name":"bar","values":[3,4]}]}]}';
        $actual = json_encode($pfr);

        $this->assertFalse($pfr->isValid());
        $this->assertFalse($pfr->isEmpty());

        $this->assertEquals($expected, $actual);
    }

    public function test_perfdata_merge_without_data()
    {
        $pfr = new PerfdataResponse();

        $ds = new PerfdataSet('myset', 'theunit');

        $s1 = new PerfdataSeries('foo', [1,2]);
        $s2 = new PerfdataSeries('bar', [3,4]);

        $ds->addSeries($s1);
        $ds->addSeries($s2);

        $pfr->addDataset($ds);

        $pfr->mergeCustomVars([]);

        $expected = '{"errors":[],"data":[{"title":"myset","unit":"theunit","show_thresholds":true,"timestamps":[],"series":[{"name":"foo","values":[1,2]},{"name":"bar","values":[3,4]}]}]}';
        $actual = json_encode($pfr);

        $this->assertFalse($pfr->isValid());

        $this->assertEquals($expected, $actual);
    }

    public function test_perfdata_merge_with_data()
    {
        $pfr = new PerfdataResponse();

        $ds = new PerfdataSet('myset', 'theunit');

        $s1 = new PerfdataSeries('foo', [1,2]);
        $s2 = new PerfdataSeries('bar', [3,4]);

        $ds->addSeries($s1);
        $ds->addSeries($s2);

        $pfr->addDataset($ds);

        $customvars = [
            'myset' => [
                'unit' => 'load',
                'fill' => 'rgba(1, 1, 1, 1)',
                'stroke' => 'rgba(2, 2, 2, 2)',
            ]
        ];

        $pfr->mergeCustomVars($customvars);

        $expected = '{"errors":[],"data":[{"title":"myset","unit":"load","fill":"rgba(1, 1, 1, 1)","stroke":"rgba(2, 2, 2, 2)","show_thresholds":true,"timestamps":[],"series":[{"name":"foo","values":[1,2]},{"name":"bar","values":[3,4]}]}]}';
        $actual = json_encode($pfr);

        $this->assertFalse($pfr->isValid());

        $this->assertEquals($expected, $actual);
    }

    public function test_perfdata_isvalid()
    {
        $pfr = new PerfdataResponse();

        $ds = new PerfdataSet('myset', 'theunit');
        $ds->setTimestamps([1,2]);

        $s1 = new PerfdataSeries('foo', [1,2]);
        $s2 = new PerfdataSeries('bar', [3,4]);

        $ds->addSeries($s1);
        $ds->addSeries($s2);

        $pfr->addDataset($ds);

        $this->assertTrue($pfr->isValid());

        $this->assertFalse($pfr->hasErrors());
    }

    public function test_perfdata_setDatasetToHighlight()
    {
        $pfr = new PerfdataResponse();

        $ds1 = new PerfdataSet('myset1', 'theunit1');
        $ds2 = new PerfdataSet('myset2', 'theunit2');
        $ds3 = new PerfdataSet('myset3', 'theunit3');

        $pfr->addDataset($ds1);
        $pfr->addDataset($ds2);
        $pfr->addDataset($ds3);

        $expected = '{"errors":[],"data":[{"title":"myset1","unit":"theunit1","show_thresholds":true,"timestamps":[],"series":[]},{"title":"myset2","unit":"theunit2","show_thresholds":true,"timestamps":[],"series":[]},{"title":"myset3","unit":"theunit3","show_thresholds":true,"timestamps":[],"series":[]}]}';
        $actual = json_encode($pfr);
        $this->assertEquals($expected, $actual);

        $pfr->setDatasetToHighlight('foobar');

        $pfr->setDatasetToHighlight('myset3');

        $expected = '{"errors":[],"data":[{"title":"myset3","unit":"theunit3","show_thresholds":true,"timestamps":[],"series":[]},{"title":"myset1","unit":"theunit1","show_thresholds":true,"timestamps":[],"series":[]},{"title":"myset2","unit":"theunit2","show_thresholds":true,"timestamps":[],"series":[]}]}';
        $actual = json_encode($pfr);
        $this->assertEquals($expected, $actual);
    }

}
