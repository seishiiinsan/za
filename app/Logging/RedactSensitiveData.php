<?php

namespace App\Logging;

use Illuminate\Log\Logger;

/** Branche le masquage de `system_id` sur un canal de log. */
class RedactSensitiveData
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->pushProcessor(new RedactSystemId);
        }
    }
}
