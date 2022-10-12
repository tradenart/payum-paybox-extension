<?php 
namespace Tradenart\Payum\Paybox\Listener;

use Payum\Core\Action\CapturePaymentAction;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Extension\Context;
use Payum\Core\Bridge\Symfony\Event\ExecuteEvent;
use Payum\Core\Request\Notify;
use App\Service\CommandeService;

class NotifyPaymentListener implements ExtensionInterface
{
    
    private $commandeService;
    
    public function __construct(CommandeService $commandeService)
    {
        $this->commandeService = $commandeService;
    }
    
    public function onPreExecute($context)
    {
        dump($context);
        die(0);
    }
    
    public function onExecute($context)
    {
        dump($context);
        die(0);
    }
    
    public function onPostExecute($event)
    {
        $context = $event->getContext();
        
        $request = $context->getRequest();
        
        if (false == $request instanceof Notify) {
            return;
        }else{
            $token = $request->getToken();
            
            $context->getGateway()->execute($status = new GetHumanStatus($token));
            $payment = $status->getFirstModel();
            
            $paymentModel = $request->getModel();
            $context->getGateway()->execute($status = new GetHumanStatus($paymentModel));
                        
            $this->commandeService->valideCommande($payment,$status);
        }
                
        if ($request instanceof GetStatusInterface) {
            return;
        }
        
        
    }
}
