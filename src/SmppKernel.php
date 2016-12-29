<?php

namespace egi\SmppKernel;

use \Net_SMPP_Command_Deliver_Sm;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SmppKernel implements SmppKernelInterface
{
    protected $dispatcher;

    function __construct(EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public static function respond(\Net_SMPP_Command $sm)
    {
        $m = \Net_SMPP::PDU($sm->command.'_resp', array(
            'sequence' => $sm->sequence,
        ));
        return $m;
    }

    public static function resend(Net_SMPP_Command_Deliver_Sm $sm)
    {
        $m = Net_SMPP::PDU('submit_sm', array(
            // shortcode
            'source_addr'         => $sm->source_addr,
            'source_addr_ton'     => NET_SMPP_TON_NWSPEC,
            'source_addr_npi'     => NET_SMPP_NPI_UNK,

            // 6281xxxx format
            'destination_addr'    => $sm->destination_addr,
            'dest_addr_ton'       => NET_SMPP_TON_INTL,
            'dest_addr_npi'       => NET_SMPP_NPI_ISDN,
        ));
        return $m;
    }

    public static function reply(Net_SMPP_Command_Deliver_Sm $sm)
    {
        $m = Net_SMPP::PDU('submit_sm', array(
            // shortcode
            'source_addr'         => $sm->destination_addr,
            'source_addr_ton'     => NET_SMPP_TON_NWSPEC,
            'source_addr_npi'     => NET_SMPP_NPI_UNK,

            // 6281xxxx format
            'destination_addr'    => $sm->source_addr,
            'dest_addr_ton'       => NET_SMPP_TON_INTL,
            'dest_addr_npi'       => NET_SMPP_NPI_ISDN,
        ));
        return $m;
    }

    var $smsc = null;

    static $_instance;
    public static function bind($smsc) {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        self::$_instance->smsc = $smsc;
        return self::$_instance;
    }

    public function handleMo(Net_SMPP_Command_Deliver_Sm $sm) {

        $this->dispatcher->addListener(SmppKernelEvents::REQUEST, function(Event $ev) {
            // TODO: put in queue
            $result = null;
            if (false === $result) {
                $rsm = SmppKernel::respond($ev->getRequest());
                $rsm->status = NET_SMPP_ESME_RMSGQFUL;

                $ev->setResponse($rsm);
            }
        });

        $this->dispatcher->addListener(SmppKernelEvents::REQUEST, function($rsm) {
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
            //$rsm->message_id = TmlogModel::$last_transaction_id;
        });

        $event = new GetResponseEvent($this, $sm);
        $this->dispatcher->dispatch(SmppKernelEvents::REQUEST, $event);

        if ($event->hasResponse()) {
            $rsm = $event->getResponse();
            if ($rsm->status !== NET_SMPP_ESME_ROK) {
                return $this->filterResponse($rsm, $sm);
            }
        }

        // TODO: resolver
        $controller = array($this, 'alp');
        $arguments = array(array('reg', 'mukidi'));
        //$rsm = $this->container->get('router')->dispatch($sm, $this);
        $rsm = call_user_func_array($controller, $arguments);

        return $this->filterResponse($rsm, $sm);
    }

    public function alp($iod) {
        return 'hello world';
    }

    public function alp_go_xl97799($iod) {
        $alp = new Go_Xl97799();
        $result = $alp->on_reg($sm);
        if (false === $result) {
            $rsm->status = NET_SMPP_ESME_RX_T_APPN;
        }
    }

    public function filterResponse($response, $request) {
        $this->dispatcher->dispatch(SmppKernelEvents::RESPONSE);
        $this->finishRequest($request);
        return $response;
    }

    public function finishRequest($request) {
        $this->dispatcher->dispatch(SmppKernelEvents::FINISH_REQUEST);
    }

    public function handleDr(Net_SMPP_Command_Deliver_Sm $sm) {

        $rsm = SmppKernel::respond($sm);

        // TODO: try to put in queue, or else give 0x14 resp
        $result = null;
        if (false === $result) {
            $rsm->status = NET_SMPP_ESME_RMSGQFUL;
            return $rsm;
        }

        if ($sm->message_state & (\NET_SMPP_STATE_ENROUTE | \NET_SMPP_STATE_ACCEPTED | \NET_SMPP_STATE_REJECTED)) {
            // event: onSubmitSmsc()
            // TmlogModel::saveServerMessageId($sm->sequence, $sm->receipted_message_id);
        } elseif ($sm->message_state & (\NET_SMPP_STATE_DELIVERED | \NET_SMPP_STATE_UNDELIVERABLE)) {
            // event: onDeliverSm() | onSubmitComplete()
            // TmlogModel::changeMessageStatus($sm->receipted_message_id, $sm->message_state);
        }

        // if somehow saving to tmlog failed
        if (false === $result) {
            $rsm->status = NET_SMPP_ESME_RDELIVERYFAILURE;
            return $rsm;
        }

        //$rsm = $this->container->get('router')->dispatch($sm, $this);
        $alp = new Go_Xl97799();
        $result = $alp->on_reg_delivered($sm);
        if (false === $result) {
            $rsm->status = NET_SMPP_ESME_RX_T_APPN;
        }

        return $rsm;
    }

    public static function handleMt(\Net_SMPP_Command_Submit_Sm $sm) {
        //$this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->update($sm);

        $rm = self::$_instance->smsc->send($sm);

        //$this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->update($rm);

        return $rm;
    }
}

