<?php

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Tear down the test case.
     */
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }
}
