<?php


namespace Loopy;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Mpesa
{
    const SANDBOX_URL = "https://sandbox.safaricom.co.ke/";

    /**
     * todo fill production url later
     */

    const PRODUCTION_URL = "";

    protected $live = false;
    protected $consumer_key;
    protected $consumer_secret;
    protected $client;
    protected $debug;
    protected $proxy;

    /**
     * Mpesa constructor.
     */
    public function __construct($key, $secret, $debug = false, $live = false)
    {
        $this->consumer_key = $key;
        $this->consumer_secret = $secret;
        $this->live = $live;
        $this->client = new Client(['base_uri' => $this->getBaseUrl(), 'http_errors' => false]);
    }

    /**
     * @return mixed
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param mixed $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        $this->client = new Client(['base_uri' => $this->getBaseUrl(), 'verify' => false, 'proxy' => $this->proxy, 'http_errors' => false]);
    }

    /**
     * @return mixed
     */
    public function getConsumerKey()
    {
        return $this->consumer_key;
    }

    /**
     * @param mixed $consumer_key
     */
    public function setConsumerKey($consumer_key)
    {
        $this->consumer_key = $consumer_key;
    }

    /**
     * @return mixed
     */
    public function getConsumerSecret()
    {
        return $this->consumer_secret;
    }

    /**
     * @param mixed $consumer_secret
     */
    public function setConsumerSecret($consumer_secret)
    {
        $this->consumer_secret = $consumer_secret;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->isLive() ? static::PRODUCTION_URL : static::SANDBOX_URL;
    }

    /**
     * @return bool
     */
    public function isLive()
    {
        return $this->live;
    }

    /**
     * @param bool $live
     */
    public function setLive($live)
    {
        $this->live = $live;
    }

    public function getToken()
    {
        $endPoint = "oauth/v1/generate?grant_type=client_credentials";
        $token = base64_encode("$this->consumer_key:$this->consumer_secret");
        $options = [RequestOptions::HEADERS => ['Accept' => "application/json",
            "Authorization" => "Basic $token"]
        ];
        $response = $this->get($endPoint, $options);
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents())->access_token;
        } else {
            throw  new AuthenticationException("Invalid grant credentials");
        }
    }

    public function registerCallBack($shortcode, $confirmation_url, $validation_url)
    {

        $endPoint = "/mpesa/c2b/v1/registerurl";
        $token = $this->getToken();

        $payload = (object)["ShortCode" => $shortcode,
            "ResponseType" => "Cancelled",
            "ConfirmationURL" => $confirmation_url,
            "ValidationURL" => $validation_url
        ];

        $options = [RequestOptions::HEADERS => ['Content-Type' => "application/json",
            "Authorization" => "Bearer $token"],
            RequestOptions::JSON => $payload
        ];

        $response = $this->post($endPoint, $options);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            throw  new AuthenticationException("Invalid grant credentials");
        }
    }

    public function transact($shortCode, $amount, $misdn, $reference)
    {
        $payload = ["ShortCode" => $shortCode, "CommandID" => "CustomerPayBillOnline", "Amount" => $amount, "Msisdn" => $misdn, "BillRefNumber" => $reference];
        $endPoint = "/mpesa/c2b/v1/simulate";
        $token = $this->getToken();

        $options = [RequestOptions::HEADERS => ['Content-Type' => "application/json",
            "Authorization" => "Bearer $token"],
            RequestOptions::JSON => $payload
        ];

        $response = $this->post($endPoint, $options);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            throw  new AuthenticationException("Error Occurred");
        }
    }

    protected function getEndPoint($url)
    {
        return $this->getBaseUrl() . $url;
    }

    protected function get($endPoint, $options = [])
    {
        $options[RequestOptions::DEBUG] = $this->debug;
        return $this->client->get($endPoint, $options);
    }

    protected function post($endPoint, $options = [])
    {
        $options[RequestOptions::DEBUG] = $this->debug;
        return $this->client->post($endPoint, $options);
    }
}