<?php
namespace Tradenart\Payum\Paybox\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    const SUCCESS = "00000";
    
    const CONTACT_CARD_OWNER = "01";
    
    const INVALID_TRANSACTION = "12";
    
    const INVALID_AMOUNT = "13";
    
    const INVALID_HOLDER_NUMBER = "14";
    
    const CUSTOM_CANCELATION = "17";
    
    const RETRY_LATER = "19";
    
    const EXPIRED_CARD = "33";
    
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        
        $model = ArrayObject::ensureArrayObject($request->getModel());
        
        if (null === $model['erreur']) {
            $request->markNew();
            return;
        }
        
        if (self::SUCCESS === $model['erreur']) {
            $request->markCaptured();
            return;
        }
        
        $request->markFailed();
        
    }
    
    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
        $request instanceof GetStatusInterface &&
        $request->getModel() instanceof \ArrayAccess
        ;
    }
}
