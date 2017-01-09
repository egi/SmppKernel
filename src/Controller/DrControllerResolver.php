<?php

// Controller/DrControllerResolver.php

namespace egi\SmppKernel\Controller;

use \Net_SMPP_Command_Deliver_Sm;

use egi\SmppKernel\Controller\ArgumentResolverInterface;
use egi\SmppKernel\Controller\ControllerResolverInterface;

class DrControllerResolver 
    implements ControllerResolverInterface, ArgumentResolverInterface
{
    /**
     * {@inheritdoc}
     **/
    public function getController(Net_SMPP_Command_Deliver_Sm $sm)
    {
        $iods = explode(' ', strtolower($sm->short_message));
        $keyword = reset($iods);
        $controller = 'AppBundle\\SmppController\\'.ucfirst($keyword).'Controller';

        //if ($sm->message_state & (\NET_SMPP_STATE_ENROUTE | \NET_SMPP_STATE_ACCEPTED | \NET_SMPP_STATE_REJECTED)) {
            // event: onSubmitSmsc()
            // TmlogModel::saveServerMessageId($sm->sequence, $sm->receipted_message_id);
        //} elseif ($sm->message_state & (\NET_SMPP_STATE_DELIVERED | \NET_SMPP_STATE_UNDELIVERABLE)) {
            // event: onDeliverSm() | onSubmitComplete()
            // TmlogModel::changeMessageStatus($sm->receipted_message_id, $sm->message_state);
        //}

        $suffix = 'Undelivered';
        if ($sm->message_state & (\NET_SMPP_STATE_ENROUTE | \NET_SMPP_STATE_ACCEPTED | \NET_SMPP_STATE_REJECTED)) {
            $suffix = 'Submitted';
        } elseif ($sm->message_state & \NET_SMPP_STATE_DELIVERED) {
            $suffix = 'Delivered';
        }

        if (isset($iods[1])) {
            $method_parts = array('on', ucfirst($iods[1]), $suffix);
            $method = implode('', $method_parts);
            if (method_exists($controller, $method)) {
                return array($this->createController($controller), $method);
            }
        }

        $method_parts = array('default', $suffix);
        $method = implode('', $method_parts);
        if (method_exists($controller, $method)) {
            return array($this->createController($controller), $method);
        }

        if (function_exists($controller)) {
            return $controller;
        }

        throw new \Exception(sprintf('Cannot find "%s::%s()" controller class for keyword "%s"', $controller, $method, $keyword));
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
