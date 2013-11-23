<?php

use Way\Tests\Factory;

class CoreConfigTest extends TestCase{

    /** @var Laragento\CoreConfig $model */
    private $model = null;

    public function setUp()
    {
        $this->model = Factory::make('Laragento\CoreConfig', array(
            'config_id' => 1,
            'scope'     => 'default',
            'scope_id'  => '0',
            'path'      => 'dev/log/file',
            'value'     => 'system.log'
        ));
    }

    public function testConfigIdSetProperly()
    {
        $expect = 1;

        $received = $this->model->config_id;

        $this->assertEquals($expect, $received);
    }

    public function testPreparesOutputAsArray()
    {
        $received = $this->model->prepareOutput('v1');

        $this->assertTrue(is_array($received));
    }

    public function testApiVersionNotRequiredForPrepareOutput()
    {
//        $mock = Mockery::mock('Test');
//        $mock->shouldReceive('hello');

        $mock = Mockery::mock('Test');
        $mock->shouldReceive('getName')
            ->once()
            ->andReturn('John Doe');

        $received = $this->model->prepareOutput('');

        $this->assertTrue(is_array($received));


    }

}