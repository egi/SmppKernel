<?php

// Controller/MoControllerResolver.php

namespace egi\SmppKernel\Controller;

use \Net_SMPP_Command_Deliver_Sm;

use egi\SmppKernel\Controller\ArgumentResolverInterface;
use egi\SmppKernel\Controller\ControllerResolverInterface;
use egi\SmppKernel\SmppKernel;

class MoControllerResolver 
    implements ControllerResolverInterface, ArgumentResolverInterface
{
    /**
     * {@inheritdoc}
     **/
    public function getController(Net_SMPP_Command_Deliver_Sm $sm)
    {
        $iods = explode(' ', $sm->short_message);
        $keyword = strtolower(reset($iods));
        $controller = 'AppBundle\\SmppController\\'.ucfirst($keyword).'Controller';

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

        throw new \Exception(sprintf('Cannot find "%s::%s" controller class for keyword "%s"', $controller, $method, $keyword));
    }

    protected function createController($class)
    {
        if (!class_exists($class)) {
            throw new \Exception(sprintf('Class "%s" does not exists.', $class));
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
        $arguments[] = SmppKernel::$smsc;
        return $arguments;
    }
}
