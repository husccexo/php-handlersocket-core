<?php

namespace HSCore;

class HSException extends \ErrorException {

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'PHP HandlerSocket Exception';
    }
}