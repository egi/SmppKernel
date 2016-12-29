<?php

// src/SmppKernelnterface.php

namespace egi\SmppKernel;

use \Net_SMPP_Command_Deliver_Sm;
use \Net_SMPP_Command_Deliver_Sm_Resp;

interface SmppKernelnterface
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

    /**
     * @return Net_SMPP_Command_Deliver_Sm_Resp
     **/
    public function handleMo(Net_SMPP_Command_Deliver_Sm $sm);

    /**
     * @return Net_SMPP_Command_Deliver_Sm_Resp
     **/
    public function handleDr(Net_SMPP_Command_Deliver_Sm $sm);
}
