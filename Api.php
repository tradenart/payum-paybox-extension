<?php
namespace Tradenart\Payum\Paybox;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Payum\Core\Reply\HttpPostRedirect;

class Api
{
    public const MAIN_SERVER = 'tpeweb.paybox.com';

    public const BACKUP_SERVER = 'tpeweb1.paybox.com';

    public const SANDBOX_SERVER = 'preprod-tpeweb.paybox.com';

    public const PBX_SITE = 'PBX_SITE';

    public const PBX_RANG = 'PBX_RANG';

    public const PBX_IDENTIFIANT = 'PBX_IDENTIFIANT';

    public const PBX_TOTAL = 'PBX_TOTAL';

    public const PBX_DEVISE = 'PBX_DEVISE';

    public const PBX_CMD = 'PBX_CMD';

    public const PBX_PORTEUR = 'PBX_PORTEUR';

    public const PBX_REPONDRE_A = 'PBX_REPONDRE_A';

    public const PBX_RETOUR = 'PBX_RETOUR';

    public const PBX_EFFECTUE = 'PBX_EFFECTUE';

    public const PBX_ANNULE = 'PBX_ANNULE';

    public const PBX_REFUSE = 'PBX_REFUSE';

    public const PBX_HASH = 'PBX_HASH';

    public const PBX_TIME = 'PBX_TIME';

    public const PBX_HMAC = 'PBX_HMAC';


    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];

    protected $router;

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory, $router)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->router = $router->getRouter();
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function doPayment(array $details)
    {
        $details[self::PBX_SITE]        = $this->options['site'];
        $details[self::PBX_RANG]        = $this->options['rang'];
        $details[self::PBX_IDENTIFIANT]        = $this->options['identifiant'];
        $details[self::PBX_RETOUR]        = $this->options['retour'];
        $details[self::PBX_HASH]        = $this->options['hash'];
        $details[self::PBX_HMAC]        = strtoupper($this->computeHmac($details));

        $authorizeTokenUrl = $this->getAuthorizeTokenUrl();

        throw new HttpPostRedirect($authorizeTokenUrl, $details);
    }

    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        $servers = array();
        if ($this->options['sandbox']) {
            $servers[] = self::SANDBOX_SERVER;
        } else {
            $servers = array(self::MAIN_SERVER, self::BACKUP_SERVER);
        }

        foreach ($servers as $server) {
            $doc = new \DOMDocument();
            $doc->loadHTMLFile('https://'. $server . "/load.html");

            $element = $doc->getElementById('server_status');
            if ($element && 'OK' == $element->textContent) {
                return $server;
            }
        }

        throw new RuntimeException('No server available.');
    }

    /**
     * @return string
     */
    public function getAuthorizeTokenUrl()
    {
        return sprintf(
            'https://%s/cgi/MYchoix_pagepaiement.cgi',
            $this->getApiEndpoint()
            );
    }

    /**
     * @param $hmac string hmac key
     * @param $fields array fields
     * @return string
     */
    protected function computeHmac($details)
    {
        // Si la clé est en ASCII, On la transforme en binaire
        if($this->options['sandbox']){
            $key = $this->options['hmac_dev'];
        }else{
            $key = $this->options['hmac_prod'];
        }

        $binKey = pack("H*", $key);
        $msg = self::stringify($details);

        return strtoupper(hash_hmac($details[self::PBX_HASH], $msg, $binKey));
    }

    /**
     * Makes an array of parameters become a querystring like string.
     *
     * @param  array $array
     *
     * @return string
     */
    static public function stringify(array $array)
    {
        $result = array();
        foreach ($array as $key => $value) {
            $result[] = sprintf('%s=%s', $key, $value);
        }
        return implode('&', $result);
    }

    /**
     * @return mixed
     */
    protected function getOption(array $details, string $name)
    {
        if (array_key_exists($name, $details)) {
            return $details[$name];
        }

        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return null;
    }
}
