<?php

// Controller/ControllerResolver.php

namespace egi\SmppKernel\Controller;

use \Net_SMPP_Command_Deliver_Sm;

/**
 * @see Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
 **/
interface ControllerResolverInterface
{
    /**
     * @return callable|false
     **/
    public function getController(Net_SMPP_Command_Deliver_Sm $sm);
}
