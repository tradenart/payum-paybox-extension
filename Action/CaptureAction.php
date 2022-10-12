<?php
namespace Tradenart\Payum\Paybox\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Tradenart\Payum\Paybox\Action\Api\BaseApiAwareAction;
use Tradenart\Payum\Paybox\Api;
use Payum\Core\Security\TokenInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Request\GetHttpRequest;

class CaptureAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        
        $details = ArrayObject::ensureArrayObject($request->getModel());
        
        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);
        
        if (isset($httpRequest->query['erreur'])) {
            return;
        }
        
        if (null === $details[Api::PBX_REPONDRE_A] && $request->getToken() instanceof TokenInterface) {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );
            
            $details[Api::PBX_REPONDRE_A] = $notifyToken->getTargetUrl();
        }
        
        $this->api->doPayment((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
