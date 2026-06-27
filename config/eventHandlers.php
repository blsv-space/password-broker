<?php

declare(strict_types=1);

use App\Module\Identity\Application\Event\IdentityEventHandlerRegistrar;
use App\Module\PasswordBroker\Application\Event\PasswordBrokerEventHandlerRegistrar;

IdentityEventHandlerRegistrar::register();
PasswordBrokerEventHandlerRegistrar::register();
