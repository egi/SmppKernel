<?php

// Controller/MoControllerResolver.php

namespace egi\SmppKernel\Controller;

use \Net_SMPP_Command_Deliver_Sm;

use egi\SmppKernel\Controller\ArgumentResolverInterface;
use egi\SmppKernel\Controller\ControllerResolverInterface;

class MoControllerResolver 
    implements ControllerResolverInterface, ArgumentResolverInterface
{
    /**
     * {@inheritdoc}
     **/
    public function getController(Net_SMPP_Command_Deliver_Sm $sm)
    {
        $iods = explode(' ', $sm->short_message);
        $controller = reset($iods);

        if (isset($iods[1])) {
            $method = 'on_'.$iods[1];
            if (method_exists($controller, $method)) {
                return array($this->createController($controller), $method);
            }
        }

        $method = 'on_pull';
        if (method_exists($controller, $method)) {
            return array($this->createController($controller), $method);
        }

        if (function_exists($controller)) {
            return $controller;
        }

        throw new \Exception(sprintf('No controller found for keyword "%s"', $controller));
    }

    protected function createController($class)
    {
        if (!class_exists($class)) {
            throw new \Exception(sprintf('Class "%s" not exists.', $class));
        }
        return $this->instantiateController($class);
    }

    protected function instantiateController($class)
    {
        return new $class();
    }

    public function getArguments(Net_SMPP_Command_Deliver_Sm $sm, $controller)
    {
        $arguments = array();
        $arguments[] = explode(' ', $sm->short_message);
        return $arguments;
    }
}
