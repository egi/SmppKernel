<?php

use egi\SmppKernel\SmppKernel;

class SmppKernelTest extends PHPUnit_Framework_TestCase
{
    public function testRespond()
    {
        $sm = \Net_SMPP::PDU('deliver_sm', array('sequence'=>12345));
        $rm = SmppKernel::respond($sm);
        $this->assertEquals($rm->sequence, 12345);
        $this->assertEquals($rm->command, 'deliver_sm_resp');
    }
}
