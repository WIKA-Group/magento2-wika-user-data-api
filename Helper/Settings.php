<?php

declare(strict_types=1);

namespace WikaGroup\WikaUserDataApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Settings extends AbstractHelper
{
    public function getTenantId(): string
    {
        return (string)$this->scopeConfig->getValue('wika_user_data_api/connection/tenant_id', ScopeInterface::SCOPE_STORES);
    }

    public function getClientId(): string
    {
        return (string)$this->scopeConfig->getValue('wika_user_data_api/connection/client_id', ScopeInterface::SCOPE_STORES);
    }

    public function getClientSecret(): string
    {
        return (string)$this->scopeConfig->getValue('wika_user_data_api/connection/client_secret', ScopeInterface::SCOPE_STORES);
    }

    public function getResource(): string
    {
        return (string)$this->scopeConfig->getValue('wika_user_data_api/connection/resource', ScopeInterface::SCOPE_STORES);
    }

    public function getApiBaseUrl(): string
    {
        return (string)$this->scopeConfig->getValue('wika_user_data_api/connection/api_base_url', ScopeInterface::SCOPE_STORES);
    }
}