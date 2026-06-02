<?php

namespace App\Services\Identity;

use App\Services\Identity\Drivers\FakeReader;
use App\Services\Identity\Drivers\HawytiReader;
use App\Services\Identity\Drivers\MrzOcrReader;
use App\Services\Identity\Drivers\PaciReader;
use App\Services\Identity\Drivers\SmartCardReader;
use Illuminate\Support\Manager;

/**
 * Resolves the configured identity capture route (§5). New routes are added by
 * implementing IdentityReaderInterface and registering a create*Driver method.
 *
 * @method IdentityReaderInterface driver(string|null $driver = null)
 */
class IdentityReaderManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('identity.default', 'fake');
    }

    protected function createPaciDriver(): IdentityReaderInterface
    {
        return new PaciReader;
    }

    protected function createHawytiDriver(): IdentityReaderInterface
    {
        return new HawytiReader;
    }

    protected function createSmartcardDriver(): IdentityReaderInterface
    {
        return new SmartCardReader;
    }

    protected function createMrzOcrDriver(): IdentityReaderInterface
    {
        return new MrzOcrReader($this->container->make(MrzParser::class));
    }

    protected function createFakeDriver(): IdentityReaderInterface
    {
        return new FakeReader($this->container->make(MrzParser::class));
    }
}
