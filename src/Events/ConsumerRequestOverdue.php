<?php

namespace JeffersonGoncalves\CcpaRequests\Events;

use JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest;

class ConsumerRequestOverdue
{
    public function __construct(public ConsumerRequest $request) {}
}
