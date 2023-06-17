# Midtrans Gateway

This is a nonmerchant gateway for Blesta that integrates with [Midtrans](https://midtrans.com/id/passport).

## Install the Gateway

1. Upload the source code to a /components/gateways/nonmerchant/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/gateways/nonmerchant/
    ```

2. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

3. Find the Midtrans gateway and click the "Install" button to install it

4. Enter data Merchant ID, Client Key, Server Key

4. Setting the Midtrans Callback by the way
> Login to your Midtrans account > 'Settings' > 'Configuration'
    In the Payment Notification URL, enter https://domain.com/blesta_directory/callback/gw/1/midtrans,
    If you want to fill in the redirect url finish, unfinish, and error, you can fill it like this https://domain.com/client/pay/received/midtrans/.

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|>= v4.9.0|v1.0.3|
