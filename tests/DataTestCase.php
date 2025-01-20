<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class DataTestCase extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected $output;

    protected $connectionsToTransact;

    public function __construct($name = null, $app = null, $faker = null)
    {
        if (! $name) {
            $name = 'DataTestCase';
        }

        parent::__construct($name);

        if ($app) {
            $this->app = $app;
        }
        if ($faker) {
            $this->faker = $faker;
        }

        $this->output = new ConsoleOutput;
        $this->connectionsToTransact = [
            'pgsql',
        ];
    }
}
