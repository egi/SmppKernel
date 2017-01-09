<?php

namespace egi\SmppKernel;

use \Net_SMPP;
use \Net_SMPP_Command;
use \Net_SMPP_Command_Deliver_Sm;
use \Net_SMPP_Command_Deliver_Sm_Resp;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use egi\SmppKernel\Controller\ControllerResolverInterface;
use egi\SmppKernel\Controller\DrControllerResolver;
use egi\SmppKernel\Controller\MoControllerResolver;
use egi\SmppKernel\EventListener\DrListener;
use egi\SmppKernel\EventListener\MoListener;
use egi\SmppKernel\EventListener\SendMtListener;
use egi\SmppKernel\Event\FilterControllerArgumentsEvent;
use egi\SmppKernel\Event\FilterControllerEvent;
use egi\SmppKernel\Event\GetResponseEvent;
use egi\SmppKernel\SmppKernelEvents;

class SmppKernel implements SmppKernelInterface
{
    protected $dispatcher;
    protected $resolver;

    function __construct(EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
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

        if (false === $controller = $this->resolver->getController($sm)) {
            throw new \Exception('Controller cannot be resolved.');
        }

        $event = new FilterControllerEvent($this, $controller, $sm);
        $this->dispatcher->dispatch(SmppKernelEvents::CONTROLLER, $event);
        $controller = $event->getController();

        $arguments = $this->resolver->getArguments($sm, $controller);

        $event = new FilterControllerArgumentsEvent($this, $controller, $arguments, $sm);
        $this->dispatcher->dispatch(SmppKernelEvents::CONTROLLER_ARGUMENTS, $event);
        $controller = $event->getController();
        $arguments = $event->getArguments();

        $rsm = call_user_func_array($controller, $arguments);

        if (is_bool($rsm)) {
            $rsm = Net_SMPP::respond($sm);
            if ($rsm === false) {
                $rsm->status = NET_SMPP_ESME_RX_T_APPN;
            }
        } elseif (!$rsm instanceof Net_SMPP_Command_Deliver_Sm_Resp) {
            throw new \Exception('Undefined or invalid response.');
        }

        return $this->filterResponse($rsm, $sm);
    }

    public function filterResponse($response, $request) {
        $this->dispatcher->dispatch(SmppKernelEvents::RESPONSE);
        $this->finishRequest($request);
        return $response;
    }

    public function finishRequest($request) {
        $this->dispatcher->dispatch(SmppKernelEvents::FINISH_REQUEST);
    }

    static $smsc;
    public function bind($smsc, $state, $event) {
        if (is_null(self::$smsc)) {
            self::$smsc = $smsc;
        }

        switch($event) {
        case self::EVENT_MO:
            $this->dispatcher->addSubscriber(new MoListener());
            $this->dispatcher->addSubscriber(new SendMtListener());
            $this->resolver = new MoControllerResolver();
            return $event;

        case self::EVENT_DR:
            $this->dispatcher->addSubscriber(new DrListener());
            $this->dispatcher->addSubscriber(new SendMtListener());
            $this->resolver = new DrControllerResolver();
            return $event;
        }

        throw new \Exception('Cannot handle unknown event.');
    }

    // @deprecated. Use $smsc->send() directly instead, as $smsc is passed to SmppController.
    public static function handleMt(\Net_SMPP_Command_Submit_Sm $sm) {
        //$this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->update($sm);

        $rm = self::$smsc->send($sm);

        //$this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->update($rm);

        return $rm;
    }
}

