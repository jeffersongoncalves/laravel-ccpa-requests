<?php

return [

    'table' => 'ccpa_consumer_requests',

    /*
     * Confirm receipt within 10 business days (CCPA Regulations §7021(a)).
     */
    'acknowledge_business_days' => 10,

    /*
     * Answer within 45 calendar days of receipt (Cal. Civ. Code §1798.130(a)(2)(A)).
     */
    'deadline' => '45 days',

    /*
     * One extension of 45 more days when reasonably necessary. Tell the consumer, with the
     * reasons, within the first 45 days (§1798.130(a)(2)(A)).
     */
    'extension' => '45 days',

    /*
     * Where exports (JSON, right to know) are written. Keep it a private disk:
     * the file holds the consumer's personal information.
     */
    'disk' => 'local',
    'directory' => 'ccpa/requests',

];
