<?php

namespace egi\SmppKernel\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SendMtListener implements EventSubscriberInterface
{
    public function sendMt(Event $ev)
    {
        //$this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->update($sm);

        $rm = self::$_instance->smsc->send($sm);

        //$this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->update($rm);
    }

    public static function getSubscribedEvents()
    {
        return array(
            SmppKernelEvents::FINISH_REQUEST => 'sendMt',
        );
    }
}