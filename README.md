# PhantomAuth
A PHP Authentication System with modular resource storage, rate-limiting, ip-limiting, keys, and configuration.

I've been using this authentication system for a long time in my projects but it had some major flaws and security issues that I needed to fix. After fixing everything, I've decided to release the auth here for others to use.
PhantomAuth integrates with my other project, CMAnalytics, for powerful discord logging (if you enable that feature.)

⚠️ **I am not liable for any misuse of this authentication system such as using it to 'grab IPs' or collect other user data for malicious intent. My software is provided for educational purposes only.**

The Authentication System can easily manage multiple products, tiers, and users completely server-side. In addition, it includes the following features:
* Rate-limit requests of users
* IP-limit each key so that only one (or more) users can access it
* Log requests to a Discord Webhook using CMAnalytics
* Lock content behind keys so that they can only be accessed by authenticated users

**All of these features are configurable**, so you can enable, disable, and change them to your liking.

# Setup Guide

## 1. Download the Authentication System
You can download from the "releases" section of this repository.

## 2. Import the Authentication System to your hosting provider
If you have RDP or file-access to your website, you can simply upload the file to some directory in your website's files, e.g. `yoursite.com/auth/` (Optionally, you can rename the file to "index.php" so that it is directly accessible from the directory.)

## 3. Configure the settings in the file
Open up the `config.json` file and configure the settings to your liking. Here is an example configuration. Each setting is pretty self-explanatory.

```json
{
    'productName': "example",
    'iplimit': {
        'enabled': true,
        'ipAddressesPerKey': 2
    },
    'ratelimit': {
        'enabled': true,
        'maxRequestsPerPeriod': 5,
        'timePeriodSeconds': 15
    },
    'logging': {
        'enabled': true,
        'webhook_url': "https://discord.com/api/webhooks/...",
        'embed_username': "PhantomAuth",
        'embed_color_hex': "#f44444",
        'embed_avatar_url': "https://raw.githubusercontent.com/owengregson/PhantomAuth/main/resources/PhantomAuth.png"
    }
}
```

## 4. Execute the script by accessing the page
Visit the website with no parameters in your request, e.g.
`https://yoursite.com/auth/`

Upon your first execution of the script, it will automatically propagate the necessary files and directories for your configured product name.

The automatically generated file structure will look something like the following:

```
├── (productName)
│   ├── data
│   │   └── example-key.ip
│   ├── keys
│   │   └── example-keys.txt
│   └── resources
│       └── example-resource.txt
└── index.php
```
## 5. Put in your license keys and make new tiers (if you want.)
Access the `./keys` directory and rename the file to whatever you want your access tier to be, e.g. "premium-keys.txt" instead of "example-keys.txt"
Then, simply add the license keys you want into that file, and they will be automatically recognized by the program. You can create new files in this format to add more tiers to your product.

## 6. All finished!
You can easily change the "productName" in the config.json to make new products and follow the setup guide inside each one once again. Any products you created previously will still authenticate just like before.
Now that you're finished with setup, you can move on to usage of the authentication system.

# Usage Guide

## 1. Sending Requests
Each request should be a GET request over HTTP or HTTPS. The URL parameters are formatted as the following:

* key: The key to attempt authentication with.
* type: The product 'tier' or type to check the key in. Must have a matching `(type)-keys.txt` file in the `./keys` directory.
* product: The product to authenticate in.
* request: (optional) The locked resource to access.

An example authentication request using these parameters would be:
`https://yoursite.com/auth/?key=example-key&type=example&product=example&request=example-resource`

## 2. Receiving Responses
Reponses are in the format of a JSON encoded object. The JSON object contains the following properties:

* product: The product that was authenticated.
* type: The product 'tier' or type that was checked.
* status: Either "valid" or "invalid" indicating the result of the authentication request.
* reason: The reason why the authentication failed (or "authorized" if it was successful.)
* response: The requested locked data from the request property or "success" if none was provided (and authentication was successful.) Can also be "The request type did not match any resource." if the script cannot find the requested resource.

An example **(successful)** authentication response would be:
```json
{
    "product": "example",
    "type": "example",
    "status": "valid",
    "reason": "authorized",
    "response": "example-resource"
}
```
And an example **(unsuccessful)** authentication response is:
```json
{
    "product": "example",
    "type": "example",
    "status": "invalid",
    "reason": "bad-request"
}
```