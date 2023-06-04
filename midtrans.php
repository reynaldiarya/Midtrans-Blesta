<?php

/**
 * Midtrans Gateway
 * The Alipay API documentation can be found at:
 * https://docs.midtrans.com/
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant_demo
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class NonmerchantDemo extends NonmerchantGateway
{
	/**
	 * @var array An array of meta data for this gateway
	 */
	private $meta;


	/**
	 * Construct a new merchant gateway
	 */
	public function __construct()
	{
		$this->loadConfig(dirname(__FILE__) . DS . 'config.json');

		// Load components required by this gateway
		Loader::loadComponents($this, array("Input"));

		// Load the language required by this gateway
		Language::loadLang("midtrans", null, dirname(__FILE__) . DS . "language" . DS);
	}

	/**
	 * Sets the currency code to be used for all subsequent payments
	 *
	 * @param string $currency The ISO 4217 currency code to be used for subsequent payments
	 */
	public function setCurrency($currency)
	{
		$this->currency = $currency;
	}

	/**
	 * Create and return the view content required to modify the settings of this gateway
	 *
	 * @param array $meta An array of meta (settings) data belonging to this gateway
	 * @return string HTML content containing the fields to update the meta data for this gateway
	 */
	public function getSettings(array $meta = null)
	{
		$this->view = $this->makeView("settings", "default", str_replace(ROOTWEBDIR, "", dirname(__FILE__) . DS));

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$this->view->set("meta", $meta);

		return $this->view->fetch();
	}

	/**
	 * Validates the given meta (settings) data to be updated for this gateway
	 *
	 * @param array $meta An array of meta (settings) data to be updated for this gateway
	 * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
	 */
	public function editSettings(array $meta)
	{
		// Verify meta data is valid
		$rules = [
			'merchant_id' => [
				'valid' => [
					'rule' => 'isEmpty',
					'negate' => true,
					'message' => Language::_('Midtrans.!error.merchant_id.valid', true)
				]
			],
			'client_key' => [
				'valid' => [
					'rule' => 'isEmpty',
					'negate' => true,
					'message' => Language::_('Midtrans.!error.client_key.valid', true)
				]
			],
			'server_key' => [
				'valid' => [
					'rule' => 'isEmpty',
					'negate' => true,
					'message' => Language::_('Midtrans.!error.server_key.valid', true)
				]
			]
		];

		// Set checkbox if not set
		if (!isset($meta['dev_mode'])) {
			$meta['dev_mode'] = 'false';
		}


		$this->Input->setRules($rules);

		// Validate the given meta data to ensure it meets the requirements
		$this->Input->validates($meta);
		// Return the meta data, no changes required regardless of success or failure for this gateway
		return $meta;
	}

	/**
	 * Returns an array of all fields to encrypt when storing in the database
	 *
	 * @return array An array of the field names to encrypt when storing in the database
	 */
	public function encryptableFields()
	{

		#
		# TODO: return an array of all meta field names to store encrypted
		#

		return ['merchant_id', 'client_key', 'server_key'];
	}

	/**
	 * Sets the meta data for this particular gateway
	 *
	 * @param array $meta An array of meta data to set for this gateway
	 */
	public function setMeta(array $meta = null)
	{
		$this->meta = $meta;
	}

	/**
	 * Returns all HTML markup required to render an authorization and capture payment form
	 *
	 * @param array $contact_info An array of contact info including:
	 * 	- id The contact ID
	 * 	- client_id The ID of the client this contact belongs to
	 * 	- user_id The user ID this contact belongs to (if any)
	 * 	- contact_type The type of contact
	 * 	- contact_type_id The ID of the contact type
	 * 	- first_name The first name on the contact
	 * 	- last_name The last name on the contact
	 * 	- title The title of the contact
	 * 	- company The company name of the contact
	 * 	- address1 The address 1 line of the contact
	 * 	- address2 The address 2 line of the contact
	 * 	- city The city of the contact
	 * 	- state An array of state info including:
	 * 		- code The 2 or 3-character state code
	 * 		- name The local name of the country
	 * 	- country An array of country info including:
	 * 		- alpha2 The 2-character country code
	 * 		- alpha3 The 3-cahracter country code
	 * 		- name The english name of the country
	 * 		- alt_name The local name of the country
	 * 	- zip The zip/postal code of the contact
	 * @param float $amount The amount to charge this contact
	 * @param array $invoice_amounts An array of invoices, each containing:
	 * 	- id The ID of the invoice being processed
	 * 	- amount The amount being processed for this invoice (which is included in $amount)
	 * @param array $options An array of options including:
	 * 	- description The Description of the charge
	 * 	- return_url The URL to redirect users to after a successful payment
	 * 	- recur An array of recurring info including:
	 * 		- amount The amount to recur
	 * 		- term The term to recur
	 * 		- period The recurring period (day, week, month, year, onetime) used in conjunction with term in order to determine the next recurring payment
	 * @return string HTML markup required to render an authorization and capture payment form
	 */
	public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
	{
		// Load the models required
		// Loader::loadModels($this, ['Companies']);

		// Load the helpers required for this view
		Loader::loadHelpers($this, ['Html']);

		// Load library methods
		Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Midtrans.php');
		\Midtrans\Config::$serverKey = $this->meta['server_key'];
		\Midtrans\Config::$isProduction = $this->meta['dev_mode'];

		// Set all invoices to pay
		// if (isset($invoice_amounts) && is_array($invoice_amounts)) {
		//     $invoices = $this->serializeInvoices($invoice_amounts);
		// }

		// Generate an order
		$transaction_details = [
			'order_id' => rand(),
			'gross_amount' => $amount,
		];

		$customer_details = array(
			'first_name'    => ($contact_info['first_name'] ?? null),
			'last_name'     => ($contact_info['last_name'] ?? null),
			// 'email'     => ($contact_info['email'] ?? null),
		);

		$params = array(
			'transaction_details' => $transaction_details,
			'customer_details' => $customer_details,
		);

		try {
			// Get Snap Payment Page URL
			$paymentUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;

			return $this->buildForm($paymentUrl);
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Builds the HTML form.
	 *
	 * @param string $post_to The URL to post to
	 * @param array $fields An array of key/value input fields to set in the form
	 * @return string The HTML form
	 */
	private function buildForm($post_to)
	{
		$this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

		// Load the helpers required for this view
		Loader::loadHelpers($this, ['Form', 'Html']);

		$this->view->set('post_to', $post_to);

		return $this->view->fetch();
	}

	/**
	 * Validates the incoming POST/GET response from the gateway to ensure it is
	 * legitimate and can be trusted.
	 *
	 * @param array $get The GET data for this request
	 * @param array $post The POST data for this request
	 * @return array An array of transaction data, sets any errors using Input if the data fails to validate
	 *  - client_id The ID of the client that attempted the payment
	 *  - amount The amount of the payment
	 *  - currency The currency of the payment
	 *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
	 *  	- id The ID of the invoice to apply to
	 *  	- amount The amount to apply to the invoice
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the gateway to identify this transaction
	 */
	public function validate(array $get, array $post)
	{

		#
		# TODO: Verify the get/post data, then return the transaction
		#
		#
		// Log the successful response
		// Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Midtrans.php');
		// \Midtrans\Config::$isProduction = $this->meta['dev_mode'];
		// \Midtrans\Config::$serverKey = $this->meta['server_key'];
		// $notif = new \Midtrans\Notification();

		// $transaction = $notif->transaction_status;
		// $type = $notif->payment_type;
		// $order_id = $notif->order_id;
		// $fraud = $notif->fraud_status;

		// if ($transaction == 'capture') {
		// 	// For credit card transaction, we need to check whether transaction is challenge by FDS or not
		// 	if ($type == 'credit_card') {
		// 		if ($fraud == 'challenge') {
		// 			// TODO set payment status in merchant's database to 'Challenge by FDS'
		// 			// TODO merchant should decide whether this transaction is authorized or not in MAP
		// 			echo "Transaction order_id: " . $order_id . " is challenged by FDS";
		// 		} else {
		// 			// TODO set payment status in merchant's database to 'Success'
		// 			echo "Transaction order_id: " . $order_id . " successfully captured using " . $type;
		// 		}
		// 	}
		// } else if ($transaction == 'settlement') {
		// 	// TODO set payment status in merchant's database to 'Settlement'
		// 	echo "Transaction order_id: " . $order_id . " successfully transfered using " . $type;
		// } else if ($transaction == 'pending') {
		// 	// TODO set payment status in merchant's database to 'Pending'
		// 	echo "Waiting customer to finish transaction order_id: " . $order_id . " using " . $type;
		// } else if ($transaction == 'deny') {
		// 	// TODO set payment status in merchant's database to 'Denied'
		// 	echo "Payment using " . $type . " for transaction order_id: " . $order_id . " is denied.";
		// } else if ($transaction == 'expire') {
		// 	// TODO set payment status in merchant's database to 'expire'
		// 	echo "Payment using " . $type . " for transaction order_id: " . $order_id . " is expired.";
		// } else if ($transaction == 'cancel') {
		// 	// TODO set payment status in merchant's database to 'Denied'
		// 	echo "Payment using " . $type . " for transaction order_id: " . $order_id . " is canceled.";
		// }
	}

	/**
	 * Returns data regarding a success transaction. This method is invoked when
	 * a client returns from the non-merchant gateway's web site back to Blesta.
	 *
	 * @param array $get The GET data for this request
	 * @param array $post The POST data for this request
	 * @return array An array of transaction data, may set errors using Input if the data appears invalid
	 *  - client_id The ID of the client that attempted the payment
	 *  - amount The amount of the payment
	 *  - currency The currency of the payment
	 *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
	 *  	- id The ID of the invoice to apply to
	 *  	- amount The amount to apply to the invoice
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- transaction_id The ID returned by the gateway to identify this transaction
	 */
	public function success(array $get, array $post)
	{

		#
		# TODO: Return transaction data, if possible
		#

		$this->Input->setErrors($this->getCommonError("unsupported"));
	}


	/**
	 * Captures a previously authorized payment
	 *
	 * @param string $reference_id The reference ID for the previously authorized transaction
	 * @param string $transaction_id The transaction ID for the previously authorized transaction
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function capture($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
	{

		#
		# TODO: Return transaction data, if possible
		#

		$this->Input->setErrors($this->getCommonError("unsupported"));
	}

	/**
	 * Void a payment or authorization
	 *
	 * @param string $reference_id The reference ID for the previously submitted transaction
	 * @param string $transaction_id The transaction ID for the previously submitted transaction
	 * @param string $notes Notes about the void that may be sent to the client by the gateway
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function void($reference_id, $transaction_id, $notes = null)
	{

		#
		# TODO: Return transaction data, if possible
		#

		$this->Input->setErrors($this->getCommonError("unsupported"));
	}

	/**
	 * Refund a payment
	 *
	 * @param string $reference_id The reference ID for the previously submitted transaction
	 * @param string $transaction_id The transaction ID for the previously submitted transaction
	 * @param float $amount The amount to refund this card
	 * @param string $notes Notes about the refund that may be sent to the client by the gateway
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function refund($reference_id, $transaction_id, $amount, $notes = null)
	{

		#
		# TODO: Return transaction data, if possible
		#

		$this->Input->setErrors($this->getCommonError("unsupported"));
	}
}
