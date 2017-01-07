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
        $iods = explode(' ', $sm->short_message);
        $controller = reset($iods);

        //if ($sm->message_state & (\NET_SMPP_STATE_ENROUTE | \NET_SMPP_STATE_ACCEPTED | \NET_SMPP_STATE_REJECTED)) {
            // event: onSubmitSmsc()
            // TmlogModel::saveServerMessageId($sm->sequence, $sm->receipted_message_id);
        //} elseif ($sm->message_state & (\NET_SMPP_STATE_DELIVERED | \NET_SMPP_STATE_UNDELIVERABLE)) {
            // event: onDeliverSm() | onSubmitComplete()
            // TmlogModel::changeMessageStatus($sm->receipted_message_id, $sm->message_state);
        //}
        $postfix = 'submitted';
        if ($sm->message_state & \NET_SMPP_STATE_DELIVERED) {
            $postfix = 'delivered';
        } elseif ($sm->message_state & \NET_SMPP_STATE_UNDELIVERABLE) {
            $postfix = 'undelivered';
        }

        if (isset($iods[1])) {
            $method_parts = array('on', $iods[1], $postfix);
            $method = implode('_', $method_parts);
            if (method_exists($controller, $method)) {
                return array($this->createController($controller), $method);
            }
        }

        $method_parts = array('on', 'pull', $postfix);
        $method = implode('_', $method_parts);
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
