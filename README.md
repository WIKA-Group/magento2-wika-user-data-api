# Mage2 Module WIKA User Data API

Magento2 Module to encapsulate the access to the WIKA user data azure API.

For example of response see internal wiki.

## Usage
```PHP
class Demo {
    public function __construct(
        private \WikaGroup\WikaUserDataApi\Helper\Data $userDataApi,
    ) { }

    public function doWork()
    {
        $userData = $this->userDataApi->getUserData('john.doe@example.com');
        if ($userData === null) {
            // No data found for given email - or error (see log file)
            return;
        }
        
        // Process data
        $userData['id'];
    }
}
```

## Configuration
The configuration can be found in the admin backend under:  
`Stores` -> `Settings` -> `Configuration` -> `WIKA GROUP` -> `WIKA User Data API`

![image](doc/Settings.png)
