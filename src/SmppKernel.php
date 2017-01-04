<?php

namespace egi\SmppKernel;

use \Net_SMPP_Command_Deliver_Sm;
use \Net_SMPP_Command_Deliver_Sm_Resp;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use egi\SmppKernel\Event;
use egi\SmppKernel\Event\GetResponseEvent;

class SmppKernel implements SmppKernelInterface
{
    protected $dispatcher;
    protected $resolver;

    function __construct(EventDispatcherInterface $dispatcher, ControllerResolverInterface $resolver) {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
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

    public function handle(Net_SMPP_Command_Deliver_Sm $sm) {

        $rsm = null;

        $event = new GetResponseEvent($this, $sm);
        $this->dispatcher->dispatch(SmppKernelEvents::REQUEST, $event);

        if ($event->hasResponse()) {
            $rsm = $event->getResponse();
            if ($rsm->status !== NET_SMPP_ESME_ROK) {
                return $this->filterResponse($rsm, $sm);
            }
        }

        // TODO: controller resolver
        //$controller = array($this, 'alp_on_pull');
        if (false === $controller = $this->resolver->getController($sm)) {
            throw new \Exception();
        }

        //$controller = array($this, 'alp_on_pull_delivered');
        $event = new Event($this, $controller, $sm);
        $this->dispatcher->dispatch(SmppKernelEvents::CONTROLLER, $event);
        $controller = $event->getController();

        // TODO: argument resolver
        $arguments = array($sm, array('reg', 'mukidi'));
        $event = new Event($this, $controller, $arguments, $sm);
        $this->dispatcher->dispatch(SmppKernelEvents::CONTROLLER_ARGUMENTS, $event);
        $controller = $event->getController();
        $arguments = $event->getArguments();

        $rsm = call_user_func_array($controller, $arguments);

        if (is_bool($rsm)) {
            $rsm = SmppKernel::response($sm);
            if ($rsm === false) {
                $rsm->status = NET_SMPP_ESME_RX_T_APPN;
            }
        } elseif (!$rsm instanceof Net_SMPP_Command_Deliver_Sm_Resp) {
            throw new \Exception('Undefined or invalid response.');
        }

        return $this->filterResponse($rsm, $sm);
    }

    public function alp_on_pull($sm, $iod) {
        $rm = SmppKernel::reply($sm);
        $rm->set(array(
            'message_payload' => 'hello world'
        ));
        return true;
    }

    public function filterResponse($response, $request) {
        $this->dispatcher->dispatch(SmppKernelEvents::RESPONSE);
        $this->finishRequest($request);
        return $response;
    }

    public function finishRequest($request) {
        $this->dispatcher->dispatch(SmppKernelEvents::FINISH_REQUEST);
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

