<?php


/**
 * This file is part of php-json-rpc
 * This class implements specifications for JSON-RPC 1.0 and 2.0
 * 
 * (c) Alex Binno - alexbinno.com
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


class JsonRpcClient {
	
	/**
	 * The server URL
	 *
	 * @var string
	 */
	private $url;
	
	/**
	 * The request id
	 *
	 * @var integer
	 */
	private $request_id = 1;
	
	/**
	 * If true, notifications are performed instead of requests
	 *
	 * @var boolean
	 */
	private $notification = false;
	
	/**
	 * Request array
	 *
	 * @var boolean
	 */
	public $request;
	
	/**
	 * Raw response
	 *
	 * @var boolean
	 */
	public $raw_response;
	
	/**
	 * Response
	 *
	 * @var boolean
	 */
	public $response;
	
	/**
	 * Response Http status code
	 *
	 * @var boolean
	 */
	public $status;
	
	/**
	 * Request JSON-RPC version 
	 *
	 * @var boolean
	 */
	public $request_jsonrpc_version;
	
	/**
	 * Response JSON-RPC version 
	 *
	 * @var boolean
	 */
	public $response_jsonrpc_version;
	
	/**
	 * Takes the connection parameters
	 *
	 * @param string $url Server URL
	 * @param mixed $request_jsonrpc_version JSON-RPC version (1 or 2) 
	 * @param boolean $debug
	 */
	public function __construct($url, $request_jsonrpc_version = 1) {
		$this->url = $url;
		
		// JSON-RPC version must be 1.0 or 2.0
		$request_jsonrpc_version = (float) $request_jsonrpc_version;
		if ($request_jsonrpc_version != 1 && $request_jsonrpc_version != 2) {
			throw new Exception('JSON-RPC version must be 1.0 or 2.0');
		}
		$this->request_jsonrpc_version = $request_jsonrpc_version;
	}
	
	/**
	 * Performs a JSON-RCP request and gets the results as an array
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	public function __call($method, $params) {
		
		// method must be a scalar value
		if (!is_scalar($method)) {
			throw new Exception('Method name has no scalar value');
		}
		
		// params must be an array
		if (is_array($params)) {
			// rid of keys
			$params = array_values($params);
		} else {
			throw new Exception('Params must be given as array');
		}
		
		// prepare the request
		$this->request = array(
			'method' => $method,
			'params' => $params,
		);
		
		if ($this->request_jsonrpc_version == 2) {
			// version 2.0
			$this->request['jsonrpc'] = '2.0';
			if (!$this->notification) {
				// increment request id on every request
				$current_id = $this->request_id++;
				$this->request['id'] = $current_id;
			}
		} else {
			// version 1.0
			if ($this->notification) {
				// version 1.0 uses a request id of NULL 
				$current_id = NULL;
			} else {
				// increment request id on every request
				$current_id = $this->request_id++;
			}
			$this->request['id'] = $current_id;
		}
		
		// encode request array to json
		$request_json = json_encode($this->request);
		
		// Build the cURL request
		$curl = curl_init($this->url);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			//CURLOPT_FOLLOWLOCATION => true,
			//CURLOPT_MAXREDIRS => 10,
			
			
			CURLOPT_HTTPHEADER => array('Content-type: application/json'),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $request_json,
		);
		curl_setopt_array($curl, $options);
		
		// execute the request
		$this->raw_response = curl_exec($curl);
		
		// decode request json to an array
		$this->response = json_decode($this->raw_response, true);
		
		// get status code
		$this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		// If there was no error, this will be an empty string
		$curl_error = curl_error($curl);
		
		curl_close($curl);
		
		// if curl cant connect
		if (!empty($curl_error)) {
			throw new Exception('Cannot connect cURL error: ' . $curl_error);
		}
		
		// return data
		if ($this->notification) {
			return true;
		} else {
			// if server sends back no response
			if (!$this->response) {
				throw new Exception('Server response error: No response.');
			}
			
			// if response id is not same as the request id
			if ($this->response['id'] != $current_id) {
				throw new Exception(
					'Server response error: Incorrect response id of "' . $this->response['id']
					. '" with request id of ' . $current_id
				);
			}
			
			// if server response includes property "error"
			if (isset($this->response['error'])) {
				throw new Exception(
					'Server response error: '. json_encode($this->response['error']) . '.'
				);
			}
			
			// only JSON-RPC 2.0 servers include the property "jsonrpc"
			if (isset($this->response['jsonrpc'])) {
				$this->response_jsonrpc_version = $this->response['jsonrpc'];
			}
			
			// return result if no errors
			return $this->response['result'];
		}
	}
	
	/**
	 * Sets the notification state of the object.
	 * In this state, notifications are performed, instead of requests.
	 *
	 * @param boolean $notification
	 */
	public function setNotification($notification) {
		$this->notification = empty($notification) ? false : true;
	}
	
}


?>