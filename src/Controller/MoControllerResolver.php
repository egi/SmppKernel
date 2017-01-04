<?php

// Controller/MoControllerResolver.php

namespace egi\SmppKernel\Controller;

class MoControllerResolver implements ControllerResolverInterface
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

        throw \Exception(sprintf('No controller found for keyword "%s"', $controller));
    }

    protected function createController($class)
    {
        if (!class_exists($class)) {
            throw \Exception(sprintf('Class "%s" not exists.', $class));
        }
        return $this->instantiateController($class);
    }

    protected function instantiateController($class)
    {
        return new $class();
    }
}
