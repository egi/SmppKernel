<?php

// src/Event/FilterResponseEvent.php
namespace egi\SmppKernel\Event;

use \Net_SMPP_Command_Deliver_Sm;
use \Net_SMPP_Command_Deliver_Sm_Resp;
use Symfony\Component\EventDispatcher\Event;
use egi\SmppKernel\SmppKernel;

class FilterResponseEvent extends SmppKernelEvent
{
    protected $response;

    public function __construct(SmppKernel $kernel, Net_SMPP_Command_Deliver_Sm $request, Net_SMPP_Command_Deliver_Sm_Resp $response) {
        parent::__construct($kernel, $request);
        $this->response = $response;
    }

    public function setResponse(Net_SMPP_Command_Deliver_Sm_Resp $rsm) {
        $this->response = $rsm;
        $this->stopPropagation();
    }

    public function getResponse() {
        return $this->response;
    }
}
