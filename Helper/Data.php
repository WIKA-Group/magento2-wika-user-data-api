<?php

declare(strict_types=1);

namespace WikaGroup\WikaUserDataApi\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private const EXPIRATION_OFFSET = 10;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        private Settings $settings,
        private \Psr\Log\LoggerInterface $logger,
        private \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        private \Magento\Framework\Filesystem\Driver\File $fileDriver,
        private \MasterZydra\UCache\Helper\UCache $ucache,
        private \Magento\Framework\Escaper $escaper,
    ) {
        parent::__construct($context);
    }

    public function getUserData(string $email, bool $retry401 = true): ?array
    {
        $token = $this->getToken();
        if ($token === null) {
            return null;
        }

        try {
            $client = new \Laminas\Http\Client();
            $client->setUri($this->settings->getApiBaseUrl() . '/users/' . $this->escaper->escapeUrl($email)); 
            $client->setMethod('GET');
            $client->setHeaders(['Authorization' => 'Bearer ' . $token]);
            $response = $client->send();

            // User found
            if ($response->getStatusCode() === 200) {
                return json_decode((string)$response->getBody(), true);
            }

            // User not found
            if ($response->getStatusCode() === 404) {
                return null;
            }

            if ($retry401 && $response->getStatusCode() === 401) {
                return $this->getUserData($email, false);
            }

            $this->logger->error(
                'WikaGroup WikaUserDataApi: API request unexpected response',
                ['response' => $response->toString()]
            );
            return null;
        } catch (\Throwable $th) {
            $this->logger->error('WikaGroup WikaUserDataApi: API request failed', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return null;
        }
    }

    private function getToken(): ?string
    {
        $token = $this->ucache->load('magento2WikaUserDataApi_token');
        if ($token !== null) {
            $expires = $token['expires'];
            if ($expires - self::EXPIRATION_OFFSET > time()) {
                return $token['token'];
            }
        }

        try {
            $provider = new \WikaGroup\WikaUserDataApi\Model\AzureProvider($this->scopeConfig, $this->settings);
            $token = $provider->getAccessToken('client_credentials');

            $this->ucache->save('magento2WikaUserDataApi_token', ['token' => $token->getToken(), 'expires' => $token->getExpires()]);

            return $token->getToken();
        } catch (\Throwable $th) {
            $this->logger->error('WikaGroup WikaUserDataApi: Failed to get token', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return null;
        }
    }
}