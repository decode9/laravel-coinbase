<?php

namespace decode9\coinbase;

use Exception;

class CoinbaseAPI
{
    protected $key;     // API key
    protected $secret;  // API secret
    protected $url;     // API base URL
    protected $version; // API version
    protected $curl;    // curl handle
    protected $time;    //Timestamp

    /**
     * Constructor for CoinbaseAPI
     *
     * @param string $key API key
     * @param string $secret API secret
     * @param string $url base URL for Coinbase API
     * @param string $version API version
     * @param bool $sslverify enable/disable SSL peer verification.  disable if using beta.api.kraken.com
     */
    public function __construct($config, $sslverify = true)
    {
        $host = $config['coinbase_host'];
        $version = $config['coinbase_version'];

        $time = new DateTime();

        if ($host && $version != null && $version >= 0) {
            $this->url = $config['coinbase_host'];
            $this->version = $config['coinbase_version'];
            $this->key = $config['coinbase_key'];
            $this->secret = $config['coinbase_secret'];
            $this->time = $time->getTimestamp();
        } else {
            throw new Exception('coinbase configuration not provided', 0);
        }

        $this->curl = curl_init();

        curl_setopt_array(
            $this->curl,
            array(
                CURLOPT_SSL_VERIFYPEER => $sslverify,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Coinbase PHP API Agent',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 300
            )
        );
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * ---------- PUBLIC FUNCTIONS ----------
     * getSpotPrice
     * getAssetInfo
     * getAssetPairs
     * getOrderBook
     * getTrades
     */

    /**
     * Get trades
     * 
     * @return array of market depth pairs
     */
    public function getSpotPrice($pair)
    {
        $method = 'prices/' . $pair . '/spot';
        return $this->queryPublic($method, [], false);
    }

    public function getAccounts()
    {
        return $this->queryPrivate("accounts");
    }

    /**
     * Query public methods
     *
     * @param string $method method name
     * @param array $request request parameters
     * @return array request result on success
     * @throws \Exception
     */
    private function queryPublic($method, array $request = array(), $type = true)
    {
        // build the POST data string
        $postdata = http_build_query($request, '', '&');

        // make request
        if (!$type) {
            curl_setopt($this->curl, CURLOPT_POST, false);
        } else {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
        }

        curl_setopt($this->curl, CURLOPT_URL, $this->url . '/' . $this->version . '/' . $method);
        $result = curl_exec($this->curl);


        if ($result === false) throw new Exception('CURL error: ' . curl_error($this->curl));

        // decode results
        $result = json_decode($result, true);

        if (!is_array($result)) throw new Exception('JSON decode error');

        return $result;
    }

    /**
     * Query private methods
     *
     * @param string $path method path
     * @param array $request request parameters
     * @return array request result on success
     * @throws CoinbaseAPIException
     */
    private function queryPrivate($method, array $request = array())
    {
        if (!isset($request['nonce'])) {
            // generate a 64 bit nonce using a timestamp at microsecond resolution
            // string functions are used to avoid problems on 32 bit systems
            $nonce = explode(' ', microtime());
            $request['nonce'] = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');
        }

        // build the POST data string
        $postdata = http_build_query($request, '', '&');

        // set API key and sign the message
        $path = '/' . $this->version . '/' . $method;

        $pathSign = $path . '?' . $postdata;
        $body = '';
        $sign = hash_hmac('sha512', hash('sha256', $this->time . 'POST' . $pathSign . $body, true), base64_decode($this->secret), true);
        $headers = array(
            'CB-ACCESS-KEY: ' . $this->key,
            'CB-ACCESS-SIGN: ' . base64_encode($sign),
            'CB-ACCESS-TIMESTAMP: ' . $this->time,
        );

        // make request
        curl_setopt($this->curl, CURLOPT_URL, $this->url . $path);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($this->curl);

        if ($result === false) throw new Exception('CURL error: ' . curl_error($this->curl), 0);


        // decode results
        $result = json_decode($result, true);

        if (!is_array($result)) throw new Exception('JSON decode error', 0);

        return $result;
    }
}
