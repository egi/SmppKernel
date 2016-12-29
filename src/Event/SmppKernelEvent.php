<?php

// src/Event/SmppKernelEvent.php

namespace egi\SmppKernel\Event;

use Symfony\Component\EventDispatcher\Event;

class SmppKernelEvent extends Event
{
    private $kernel;
    private $request;

    public function __construct($kernel, $request) {
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
