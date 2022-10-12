<?php
namespace Tradenart\Payum\Paybox;

use Tradenart\Payum\Paybox\Action\AuthorizeAction;
use Tradenart\Payum\Paybox\Action\CancelAction;
use Tradenart\Payum\Paybox\Action\ConvertPaymentAction;
use Tradenart\Payum\Paybox\Action\CaptureAction;
use Tradenart\Payum\Paybox\Action\NotifyAction;
use Tradenart\Payum\Paybox\Action\RefundAction;
use Tradenart\Payum\Paybox\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PayboxGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'paybox',
            'payum.factory_title' => 'paybox',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                Api::PBX_RETOUR          => Api::PBX_RETOUR,
                'site'           => null,
                'rang'           => null,
                'identifiant'    => null,
                'hash'           => 'SHA512',
                'retour'         => 'montant:M;ref:R;auto:A;trans:T;erreur:E;sign:K',
                'hmac_dev'         => null,
                'hmac_prod'         => null,
                'sandbox'        => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [
                'site',
                'rang',
                'identifiant',
                'hmac_dev',
                'hmac_prod',
            ];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory'], new Router());
            };
        }
    }
}
