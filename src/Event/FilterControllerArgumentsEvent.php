<?php

// src/Event/FilterControllerArgumentsEvent.php
namespace egi\SmppKernel\Event;

use Symfony\Component\EventDispatcher\Event;
use \Net_SMPP_Command_Deliver_Sm;
use egi\SmppKernel\Event\FilterControllerEvent;
use egi\SmppKernel\SmppKernel;

class FilterControllerArgumentsEvent extends FilterControllerEvent
{
    protected $arguments;

    public function __construct(SmppKernel $kernel, callable $controller, array $arguments, Net_SMPP_Command_Deliver_Sm $sm) {
        parent::__construct($kernel, $controller, $sm);
        $this->arguments = $arguments;
    }

    public function setArguments(array $arguments) {
        $this->arguments = $arguments;
        $this->stopPropagation();
    }

    public function getArguments() {
        return $this->arguments;
    }
}
