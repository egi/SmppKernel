<?php

namespace egi\SmppKernel\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use egi\SmppKernel\SmppKernelEvents;

class MoListener implements EventSubscriberInterface
{
    public function putInQueue(Event $ev)
    {
        // TODO: put in queue
        $result = null;
        if (false === $result) {
            $rsm = SmppKernel::respond($ev->getRequest());
            $rsm->status = NET_SMPP_ESME_RMSGQFUL;

            $ev->setResponse($rsm);
        }
    }

    public function startSmsTransaction(Event $ev)
    {
        // TODO: start sms transaction
        $result = null;
        //$result = $this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->create($sm);
        if (false === $result) {
            $rsm = SmppKernel::respond($ev->getRequest());
            $rsm->status = NET_SMPP_ESME_RDELIVERYFAILURE;
            $ev->setResponse($rsm);
        }

        // FIXME: where should we save tmlogid?
        // message_id in deliver_sm_resp is not used and always null
        //$rsm->message_id = TmlogModel::$last_transaction_id;
    }

    public function genericController(Event $ev)
    {

    }

    public function genericControllerArguments(Event $ev)
    {

    }

    public function genericResponse(Event $ev)
    {

    }

    public static function getSubscribedEvents()
    {
        return array(
            SmppKernelEvents::REQUEST => array(
                array('putInQueue', 10),
                array('startSmsTransaction', 9),
            ),
            //SmppKernelEvents::CONTROLLER => 'genericController',
            //SmppKernelEvents::CONTROLLER_ARGUMENTS => 'genericControllerArguments',
            //SmppKernelEvents::RESPONSE => 'genericResponse',
            //SmppKernelEvents::FINISH_REQUEST => 'sendMt',
        );
    }
}
