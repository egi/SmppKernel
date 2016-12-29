<?php

// src/Event/GetResponseEvent.php

namespace egi\SmppKernel\Event;

class GetResponseEvent extends SmppKernelEvent
{
    private $response;

    public function setResponse($rsm) {
        $this->respose = $rsm;
        $this->stopPropagation();
    }

    public function getResponse() {
        return $this->response;
    }

    public function hasResponse() {
        return null !== $this->response;
    }
}
