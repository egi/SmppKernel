<?php

// src/Event/GetResponseEvent.php
namespace egi\SmppKernel\Event;

use \Net_SMPP_Command_Deliver_Sm_Resp;

class GetResponseEvent extends SmppKernelEvent
{
    protected $response;

    public function setResponse(Net_SMPP_Command_Deliver_Sm_Resp $rsm) {
        $this->response = $rsm;
        $this->stopPropagation();
    }

    public function getResponse() {
        return $this->response;
    }

    public function hasResponse() {
        return null !== $this->response;
    }
}
