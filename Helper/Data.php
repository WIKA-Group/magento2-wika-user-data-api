<?php

declare(strict_types=1);

namespace WikaGroup\WikaUserDataApi\Helper;

use Laminas\Http\Client;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
use WikaGroup\WikaUserDataApi\Model\AzureProvider;

class Data extends AbstractHelper
{
    private const EXPIRATION_OFFSET = 10;

    public function __construct(
        Context $context,
        private Settings $settings,
        private LoggerInterface $logger,
        private DirectoryList $directoryList,
        private File $fileDriver,
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
            $client = new Client();
            $client->setUri($this->settings->getApiBaseUrl() . '/users/' . _u($email));
            $client->setMethod('GET');
            $client->setHeaders(['Authorization' => 'Bearer ' . $token]);
            $response = $client->send();

            if ($response->getStatusCode() === 200) {
                return json_decode((string)$response->getBody(), true);
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
        $token = $this->loadTokenFromFile();
        if ($token !== null) {
            $expires = $token['expires'];
            if ($expires - self::EXPIRATION_OFFSET > time()) {
                return $token['token'];
            }
        }

        try {
            $provider = new AzureProvider($this->scopeConfig, $this->settings);
            $token = $provider->getAccessToken('client_credentials');

            $this->writeTokenToFile(['token' => $token->getToken(), 'expires' => $token->getExpires()]);

            return $token->getToken();
        } catch (\Throwable $th) {
            $this->logger->error('WikaGroup WikaUserDataApi: Failed to get token', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return null;
        }
    }

    private function loadTokenFromFile(): ?array
    {
        try {
            $path = $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/wika/userDataApi.txt';
            $content = $this->fileDriver->fileGetContents($path);
        } catch (\Throwable $th) {
            $this->logger->error('WikaGroup WikaUserDataApi: Failed to load token file', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return null;
        }

        if (empty($content)) {
            return null;
        }

        try {
            return json_decode($content, true);
        } catch (\Throwable $th) {
            $this->logger->error('WikaGroup WikaUserDataApi: Failed to parse token file', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return null;
        }
    }

    private function writeTokenToFile(array $token): void
    {
        try {
            $path = $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/wika';
            if (!$this->fileDriver->isExists($path)) {
                $this->fileDriver->createDirectory($path);
            }
            $this->fileDriver->filePutContents($path . '/userDataApi.txt', json_encode($token));
        } catch (\Throwable $th) {
            $this->logger->error('WikaGroup WikaUserDataApi: Failed to write token file', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
        }
    }
}