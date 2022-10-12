<?php
namespace Tradenart\Payum\Paybox\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Tradenart\Payum\Paybox\Api;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Action\GatewayAwareAction;

class ConvertPaymentAction extends GatewayAwareAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();        
        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        
        $this->gateway->execute($currency = new GetCurrency($payment->getCurrencyCode()));
        
        $details[Api::PBX_DEVISE] = $currency->numeric;
        $details[Api::PBX_CMD] = $payment->getCommande()->getId();
        $details[Api::PBX_PORTEUR] = $payment->getClientEmail();
        $details[Api::PBX_TOTAL] = $payment->getTotalAmount();
        $details[Api::PBX_TIME] = date('c');
        
        $token = $request->getToken();
        $details[Api::PBX_EFFECTUE] = $token->getTargetUrl();
        $details[Api::PBX_ANNULE] = $token->getTargetUrl();
        $details[Api::PBX_REFUSE] = $token->getTargetUrl();

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}
