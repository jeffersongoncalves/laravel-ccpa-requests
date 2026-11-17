<?php

namespace JeffersonGoncalves\CcpaRequests\Enums;

/**
 * Consumer rights handled as verifiable requests (Cal. Civ. Code §§1798.105, 1798.106, 1798.110 and 1798.115).
 * Opting out of sale/sharing and limiting sensitive PI need no verification: see laravel-ccpa-opt-out.
 */
enum RequestType: string
{
    case Know = 'know';        // §§1798.110 and 1798.115: categories and specific pieces
    case Delete = 'delete';    // §1798.105
    case Correct = 'correct';  // §1798.106

    /**
     * Types fulfil() handles on its own; the others are answered by hand, then complete().
     */
    public function isAutomatic(): bool
    {
        return in_array($this, [self::Know, self::Delete], true);
    }
}
