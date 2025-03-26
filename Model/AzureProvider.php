<?php

namespace WikaGroup\WikaUserDataApi\Model;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use WikaGroup\WikaUserDataApi\Helper\Settings;

class AzureProvider extends GenericProvider
{
    private string $resource;

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected Settings $settings,
    ) {
        $this->resource = $this->settings->getResource();

        parent::__construct([
            'clientId' => $this->settings->getClientId(),
            'clientSecret' => $this->settings->getClientSecret(),
            'urlAccessToken' => 'https://login.microsoftonline.com/' . $this->settings->getTenantId() . '/oauth2/v2.0/token',
            'scopes' => $this->resource,
            'redirectUri' => '',
            'urlAuthorize' => '',
            'urlResourceOwnerDetails' => '',
        ]);
    }

    public function getAccessToken($grant, array $options = []): AccessTokenInterface
    {
        return parent::getAccessToken($grant, array_merge(['scope' => $this->resource], $options));
    }
}
