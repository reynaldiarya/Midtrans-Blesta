<?php

/**
 * Midtrans Gateway
 * The Midtrans API documentation can be found at:
 * https://docs.midtrans.com/
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant_demo
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Midtrans extends NonmerchantGateway
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

		if (!isset($meta['3ds_mode'])) {
			$meta['3ds_mode'] = 'false';
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
		Loader::loadModels($this, ['Clients']);
		$client = $this->Clients->get($contact_info['client_id']);

		// Load the helpers required for this view
		Loader::loadHelpers($this, ['Html']);

		// Load library methods
		Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Midtrans.php');
		\Midtrans\Config::$serverKey = $this->meta['server_key'];
		if ($this->meta['dev_mode'] === 'false') {
			\Midtrans\Config::$isProduction = true;
		} else {
			\Midtrans\Config::$isProduction = false;
		}
		if ($this->meta['3ds_mode'] === 'false') {
			\Midtrans\Config::$is3ds = false;
		} else {
			\Midtrans\Config::$is3ds = true;
		}

		$order_id = $this->serializeInvoices($invoice_amounts);

		// Generate an order
		$transaction_details = [
			'order_id' => $order_id,
			'gross_amount' => $amount,
		];

		$customer_details = array(
			'first_name'    => ($contact_info['first_name'] ?? null),
			'last_name'     => ($contact_info['last_name'] ?? null),
			'email'     	=> $client->email,
		);

		$params = array(
			'transaction_details' => $transaction_details,
			'customer_details' => $customer_details,
		);

		if (strlen($order_id) <= 50) {
			try {
				// Get Snap Payment Page URL
				$paymentUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;
				$this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($paymentUrl), 'output', true);
				return $this->buildForm($paymentUrl);
			} catch (\Exception $e) {
				echo $e->getMessage();
			}
		} else {
			$this->Input->setErrors($this->getCommonError("general"));
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
		Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Midtrans.php');
		\Midtrans\Config::$serverKey = $this->meta['server_key'];
		if ($this->meta['dev_mode'] === 'false') {
			\Midtrans\Config::$isProduction = true;
		} else {
			\Midtrans\Config::$isProduction = false;
		}
		$notif = new \Midtrans\Notification();

		$transaction = $notif->transaction_status;
		$type = $notif->payment_type;
		$status_code = $notif->status_code;
		$order_id = $notif->order_id;
		$fraud = $notif->fraud_status;
		$currency = $notif->currency;
		$amount = $notif->gross_amount;
		$transaction_id = $notif->transaction_id;

		$check_signature = hash('sha512', $order_id . $status_code . $amount . $this->meta['server_key']);
		if ($notif->signature_key == $check_signature) {
			$temp = explode('|', $order_id);
			foreach ($temp as $inv) {
				$tempclient = explode('=', $inv, 2);
				if (count($tempclient) != 2) {
					continue;
				}
				$dataclient[] = ['id' => $tempclient[0], 'amount' => $tempclient[1]];
			}

			$record = new Record();
			$client_id = $record->select('client_id')->from('invoices')->where('id', '=', $tempclient[0])->fetch();

			if ($transaction == 'capture') {
				// For credit card transaction, we need to check whether transaction is challenge by FDS or not
				if ($type == 'credit_card') {
					if ($fraud == 'challenge') {
						// TODO set payment status in merchant's database to 'Challenge by FDS'
						// TODO merchant should decide whether this transaction is authorized or not in MAP
						$status = 'declined';
						$return_status = true;
					} else {
						// TODO set payment status in merchant's database to 'Success'
						$status = 'approved';
						$return_status = true;
					}
				}
			} else if ($transaction == 'settlement') {
				// TODO set payment status in merchant's database to 'Settlement'
				$status = 'approved';
				$return_status = true;
			} else if ($transaction == 'pending') {
				// TODO set payment status in merchant's database to 'Pending'
				$status = 'pending';
				$return_status = true;
			} else if ($transaction == 'deny') {
				// TODO set payment status in merchant's database to 'Denied'
				$status = 'declined';
				$return_status = true;
			} else if ($transaction == 'expire') {
				// TODO set payment status in merchant's database to 'expire'
				$status = 'void';
				$return_status = true;
			} else if ($transaction == 'cancel') {
				// TODO set payment status in merchant's database to 'Denied'
				$status = 'void';
				$return_status = true;
			}

			$data = [
				'success' => true,
				'message' => 'Notifikasi berhasil diproses',
			];
			$response = json_encode($data);
			header('Content-Type: application/json');
			http_response_code(200);
			echo $response;
		} else {
			// Data yang akan dikirim sebagai JSON
			$data = [
				'error' => true,
				'message' => 'Signature key tidak terverifikasi',
			];
			$response = json_encode($data);
			header('Content-Type: application/json');
			http_response_code(403);
			echo $response;
		}

		$this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($transaction), 'output', $return_status);

		// Return the payment information
		return array(
			'client_id' => $client_id->client_id,
			'amount' => $amount,
			'currency' => $currency,
			'status' => $status,
			'reference_id' => null,
			'transaction_id' => $transaction_id,
			'parent_transaction_id' => null,
			'invoices' => $this->unserializeInvoices($order_id ?? null)
		);
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
		Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Midtrans.php');
		\Midtrans\Config::$serverKey = $this->meta['server_key'];
		if ($this->meta['dev_mode'] === 'false') {
			\Midtrans\Config::$isProduction = true;
		} else {
			\Midtrans\Config::$isProduction = false;
		}
		$checkorderid = $get['order_id'];
		$checktransaction = \Midtrans\Transaction::status($checkorderid);

		$transaction = $checktransaction->transaction_status;
		$type = $checktransaction->payment_type;
		$status_code = $checktransaction->status_code;
		$order_id = $checktransaction->order_id;
		$fraud = $checktransaction->fraud_status;
		$currency = $checktransaction->currency;
		$amount = $checktransaction->gross_amount;
		$transaction_id = $checktransaction->transaction_id;

		$check_signature = hash('sha512', $order_id . $status_code . $amount . $this->meta['server_key']);
		if ($checktransaction->signature_key == $check_signature) {
			$temp = explode('|', $order_id);
			foreach ($temp as $inv) {
				$tempclient = explode('=', $inv, 2);
				if (count($tempclient) != 2) {
					continue;
				}
				$dataclient[] = ['id' => $tempclient[0], 'amount' => $tempclient[1]];
			}

			$record = new Record();
			$client_id = $record->select('client_id')->from('invoices')->where('id', '=', $tempclient[0])->fetch();

			if ($transaction == 'capture') {
				// For credit card transaction, we need to check whether transaction is challenge by FDS or not
				if ($type == 'credit_card') {
					if ($fraud == 'challenge') {
						// TODO set payment status in merchant's database to 'Challenge by FDS'
						// TODO merchant should decide whether this transaction is authorized or not in MAP
						$status = 'declined';
						$return_status = true;
					} else {
						// TODO set payment status in merchant's database to 'Success'
						$status = 'approved';
						$return_status = true;
					}
				}
			} else if ($transaction == 'settlement') {
				// TODO set payment status in merchant's database to 'Settlement'
				$status = 'approved';
				$return_status = true;
			} else if ($transaction == 'pending') {
				// TODO set payment status in merchant's database to 'Pending'
				$status = 'pending';
				$return_status = true;
			} else if ($transaction == 'deny') {
				// TODO set payment status in merchant's database to 'Denied'
				$status = 'declined';
				$return_status = true;
			} else if ($transaction == 'expire') {
				// TODO set payment status in merchant's database to 'expire'
				$status = 'void';
				$return_status = true;
			} else if ($transaction == 'cancel') {
				// TODO set payment status in merchant's database to 'Denied'
				$status = 'void';
				$return_status = true;
			}
		}

		$this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($transaction), 'output', $return_status);

		// Return the payment information
		return array(
			'client_id' => $client_id->client_id,
			'amount' => $amount,
			'currency' => $currency,
			'status' => $status,
			'reference_id' => null,
			'transaction_id' => $transaction_id,
			'parent_transaction_id' => null,
			'invoices' => $this->unserializeInvoices($order_id ?? null)
		);
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

	/**
	 * Serializes an array of invoice info into a string
	 *
	 * @param array A numerically indexed array invoices info including:
	 *  - id The ID of the invoice
	 *  - amount The amount relating to the invoice
	 * @return string A serialized string of invoice info in the format of key1=value1|key2=value2
	 */
	private function serializeInvoices(array $invoices)
	{
		$str = '';
		foreach ($invoices as $i => $invoice) {
			$str .= ($i > 0 ? '|' : '') . $invoice['id'] . '-' . intval($invoice['amount']);
		}

		return $str;
	}

	/**
	 * Unserializes a string of invoice info into an array
	 *
	 * @param string A serialized string of invoice info in the format of key1=value1|key2=value2
	 * @return array A numerically indexed array invoices info including:
	 *  - id The ID of the invoice
	 *  - amount The amount relating to the invoice
	 */
	private function unserializeInvoices($str)
	{
		$invoices = [];
		$temp = explode('|', $str);
		foreach ($temp as $pair) {
			$pairs = explode('-', $pair, 2);
			if (count($pairs) != 2) {
				continue;
			}
			$invoices[] = ['id' => $pairs[0], 'amount' => $pairs[1]];
		}

		return $invoices;
	}
}
