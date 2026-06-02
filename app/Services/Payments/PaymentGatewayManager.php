<?php

namespace App\Services\Payments;

use App\Services\Payments\Gateways\KnetGateway;
use App\Services\Payments\Gateways\ManualGateway;
use App\Services\Payments\Gateways\MyFatoorahGateway;
use App\Services\Payments\Gateways\SadadGateway;
use Illuminate\Support\Manager;

/**
 * Resolves the configured payment gateway (§6.5).
 *
 * @method PaymentGatewayInterface driver(string|null $driver = null)
 */
class PaymentGatewayManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('payments.default', 'manual');
    }

    protected function createManualDriver(): PaymentGatewayInterface
    {
        return new ManualGateway;
    }

    protected function createKnetDriver(): PaymentGatewayInterface
    {
        return new KnetGateway;
    }

    protected function createMyfatoorahDriver(): PaymentGatewayInterface
    {
        return new MyFatoorahGateway;
    }

    protected function createSadadDriver(): PaymentGatewayInterface
    {
        return new SadadGateway;
    }
}
