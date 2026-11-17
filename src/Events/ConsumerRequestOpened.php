<?php

namespace JeffersonGoncalves\CcpaRequests\Events;

use JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest;

class ConsumerRequestOpened
{
    public function __construct(public ConsumerRequest $request) {}
}
