<?php

namespace egi\SmppKernel;

class SmppKernel
{
    const STATE_CLOSED = 0;
    const STATE_OPEN = 1;

    /**
     * specialized in sending MT message (ag. flusher)
     **/
    const STATE_BOUND_TX = 2;

    /**
     * specialized in handling MO/DR message. all MT message will be done in
     * flusher.
     **/
    const STATE_BOUND_RX = 3;

    /**
     * smsc->send() will be done within the same thread
     **/
    const STATE_BOUND_TRX = 4;

    public static function respond(\Net_SMPP_Command $sm)
    {
        $m = \Net_SMPP::PDU($sm->command.'_resp', array(
            'sequence' => $sm->sequence,
        ));
        return $m;
    }

    public static function resend(\Net_SMPP_Command_Deliver_Sm $sm)
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

    public static function reply(\Net_SMPP_Command_Deliver_Sm $sm)
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

    public function handleMo(\Net_SMPP_Command_Deliver_Sm $sm) {

        $rsm = SmppKernel::respond($sm);

        // TODO: try to put in queue, or else give 0x14 resp
        $result = null;
        if (false === $result) {
            $rsm->status = NET_SMPP_ESME_RMSGQFUL;
            return $rsm;
        }

        //$result = $this->container->get('doctrine')->getEntityManager()->getRepository('TmLog')
        //    ->create($sm);
        if (false === $result) {
            $rsm->status = NET_SMPP_ESME_RDELIVERYFAILURE;
            return $rsm;
        }
        $rsm->message_id = TmlogModel::$last_transaction_id;

        //$rsm = $this->container->get('router')->dispatch($sm, $this);
        $alp = new Go_Xl97799();
        $result = $alp->on_reg($sm);
        if (false === $result) {
            $rsm->status = NET_SMPP_ESME_RX_T_APPN;
        }

        return $rsm;
    }

    public function handleDr(\Net_SMPP_Command_Deliver_Sm $sm) {

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

