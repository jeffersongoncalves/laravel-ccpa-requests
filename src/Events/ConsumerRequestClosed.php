<?php

namespace JeffersonGoncalves\CcpaRequests\Events;

use JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest;

/**
 * Completed or rejected: check $request->status. Notify the requester from a listener.
 */
class ConsumerRequestClosed
{
    public function __construct(public ConsumerRequest $request) {}
}
