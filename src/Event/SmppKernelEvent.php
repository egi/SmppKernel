<?php

// src/Event/SmppKernelEvent.php
namespace egi\SmppKernel\Event;

use \Net_SMPP_Command_Deliver_Sm;
use Symfony\Component\EventDispatcher\Event;
use egi\SmppKernel\SmppKernel;

class SmppKernelEvent extends Event
{
    protected $kernel;
    protected $request;

    public function __construct(SmppKernel $kernel, Net_SMPP_Command_Deliver_Sm $request) {
        $this->kernel = $kernel;
        $this->request = $request;
    }

    public function getKernel() {
        return $this->kernel;
    }

    public function getRequest() {
        return $this->request;
    }
}
