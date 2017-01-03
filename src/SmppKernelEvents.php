<?php

// src/SmppKernelEvents.php

namespace egi\SmppKernel;

class SmppKernelEvents
{
    const REQUEST = 'smppkernel.request';
    const EXCEPTION = 'smppkernel.exception';
    const CONTROLLER = 'smppkernel.controller';
    const CONTROLLER_ARGUMENTS = 'smppkernel.controller_arguments';
    const VIEW = 'smppkernel.view';
    const RESPONSE = 'smppkernel.response';
    const TERMINATE = 'smppkernel.terminate';
    const FINISH_REQUEST = 'smppkernel.finish';
}
