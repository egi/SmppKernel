<?php

// ArgumentResolverInterface.php

namespace egi\SmppKernel\Controller;

use \Net_SMPP_Command_Deliver_Sm;

/**
 * @see Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
 **/
interface ArgumentResolverInterface
{
    /**
     * @return callable|false
     **/
    public function getArguments(Net_SMPP_Command_Deliver_Sm $sm, $controller);
}
