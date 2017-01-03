<?php

namespace egi\SmppKernel\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DrListener implements EventSubscriberInterface
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

    public function loadSmsTransaction(Event $ev)
    {
        // TODO: load sms transaction
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
        // FIXME: seharusnya ini di resolver
        if ($sm->message_state & (\NET_SMPP_STATE_ENROUTE | \NET_SMPP_STATE_ACCEPTED | \NET_SMPP_STATE_REJECTED)) {
            // event: onSubmitSmsc()
            // TmlogModel::saveServerMessageId($sm->sequence, $sm->receipted_message_id);
            $ev->setController('onSubmitSmsc');
        } elseif ($sm->message_state & (\NET_SMPP_STATE_DELIVERED | \NET_SMPP_STATE_UNDELIVERABLE)) {
            // event: onDeliverSm() | onSubmitComplete()
            // TmlogModel::changeMessageStatus($sm->receipted_message_id, $sm->message_state);
            $ev->setController($ev->getController().'_delivered');
        }
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
                array('loadSmsTransaction', 9),
            ),
            SmppKernelEvents::CONTROLLER => 'genericController',
            SmppKernelEvents::CONTROLLER_ARGUMENTS => 'genericControllerArguments',
            SmppKernelEvents::RESPONSE => 'genericResponse',
            SmppKernelEvents::FINISH_REQUEST => 'sendMt',
        );
    }
}
