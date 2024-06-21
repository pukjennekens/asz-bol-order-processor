<?php
    namespace App\API;

    use Illuminate\Support\Facades\Cache;
    use Picqer\BolRetailerV10\Client;

    class BolClient
    {
        /**
         * @var string $clientId The Bol.com API client ID.
         */
        protected static $clientId;

        public static function setClientId($clientId)
        {
            self::$clientId = $clientId;
        }

        /**
         * @var string $clientSecret The Bol.com API client secret.
         */
        protected static $clientSecret;

        public static function setClientSecret($clientSecret)
        {
            self::$clientSecret = $clientSecret;
        }

        /**
         * Get the Bol.com API client.
         * @return \Picqer\BolRetailerV10\Client
         */
        public static function getClient()
        {
            $client = new Client();
            $client->authenticateByClientCredentials(self::$clientId, self::$clientSecret);

            return $client;
        }
    }