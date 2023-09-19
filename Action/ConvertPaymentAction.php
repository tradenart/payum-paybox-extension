<?php

namespace Tradenart\Payum\Paybox\Action;

use Exception;
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
use Payum\Bundle\PayumBundle\Model\PaymentBillingInfo;

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
        $details[Api::PBX_CMD] = $payment->getNumber();
        $details[Api::PBX_PORTEUR] = $payment->getClientEmail();
        $details[Api::PBX_TOTAL] = $payment->getTotalAmount();
        $details[Api::PBX_BILLING] = $this->convertBilling($payment);
        $details[Api::PBX_SHOPPINGCART] = $this->convertShoppingCart($payment);
        $details[Api::PBX_TIME] = date('c');

        $token = $request->getToken();
        $details[Api::PBX_EFFECTUE] = $token->getTargetUrl();
        $details[Api::PBX_ANNULE] = $token->getTargetUrl();
        $details[Api::PBX_REFUSE] = $token->getTargetUrl();

        $request->setResult((array)$details);
    }

    private function convertBilling(PaymentInterface $payment): string
    {
        try {
            /**
             * @var PaymentBillingInfo $billing
             */
            $serialized = $payment->getDetails()['billing'];
        } catch (Exception $e) {
            return '';
        }

        $billing = PaymentBillingInfo::unserialize($serialized);


        $pbx_prenom = substr($this->removeSpecialChar($billing->getFirstName()), 0, 30);
        $pbx_nom = substr($this->removeSpecialChar($billing->getLastName()), 0, 30);
        $pbx_adresse1 = substr($billing->getAddress1(), 0, 50);
        $pbx_adresse2 = substr($billing->getAddress2(), 0, 50);
        $pbx_zipcode = substr($billing->getZipCode(), 0, 16);
        $pbx_city = substr($billing->getCity(), 0, 50);
        $pbx_country = $billing->getCountryCode();


        $pbx_billing = "<?xml version=\"1.0\" encoding=\"utf-8\"?><Billing><Address><FirstName>" . $pbx_nom . "</FirstName>" .
            "<LastName>" . $pbx_nom . "</LastName><Address1>" . $pbx_adresse1 . "</Address1>";
        if (strlen($pbx_adresse2) > 0) {
            $pbx_billing .= "<Address2>" . $pbx_adresse2 . "</Address2>";
        }
        $pbx_billing .= "<ZipCode>" . $pbx_zipcode . "</ZipCode>" .
            "<City>" . $pbx_city . "</City><CountryCode>" . $pbx_country . "</CountryCode>" .
            "</Address></Billing>";

        return $pbx_billing;
    }

    private function removeSpecialChar($str)
    {
        return preg_replace('/[^\p{L}0-9\-\/\']/u', ' ', $str);
    }

    private function convertShoppingCart(PaymentInterface $payment): string
    {
        try {
            $totalQuantity = intval($payment->getDetails()['shoppingCart']);
        } catch (Exception $e) {
            return '';
        }

        return "<?xml version=\"1.0\" encoding=\"utf-8\"?><shoppingcart><total><totalQuantity>" . (min($totalQuantity, 99)) . "</totalQuantity></total></shoppingcart>";
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array';
    }
}
