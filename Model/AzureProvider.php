<?php

namespace WikaGroup\WikaUserDataApi\Model;

class AzureProvider extends \League\OAuth2\Client\Provider\GenericProvider
{
    private string $resource;

    public function __construct(
        protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        protected \WikaGroup\WikaUserDataApi\Helper\Settings $settings,
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

    public function getAccessToken($grant, array $options = []): \League\OAuth2\Client\Token\AccessTokenInterface
    {
        return parent::getAccessToken($grant, array_merge(['scope' => $this->resource], $options));
    }
}
