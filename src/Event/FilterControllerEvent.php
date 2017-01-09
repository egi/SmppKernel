<?php

// src/Event/FilterControllerEvent.php
namespace egi\SmppKernel\Event;

use \Net_SMPP_Command_Deliver_Sm;
use Symfony\Component\EventDispatcher\Event;
use egi\SmppKernel\SmppKernel;

class FilterControllerEvent extends SmppKernelEvent
{
    protected $controller;

    public function __construct(SmppKernel $kernel, callable $controller, Net_SMPP_Command_Deliver_Sm $sm) {
        parent::__construct($kernel, $sm);
        $this->controller = $controller;
    }

    public function setController(callable $controller) {
        $this->controller = $controller;
        $this->stopPropagation();
    }

    public function getController() {
        return $this->controller;
    }
}
