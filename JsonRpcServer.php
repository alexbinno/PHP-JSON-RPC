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


class JsonRpcServer {
	
	/**
	 * Handles methods of given object as "procedure" calls
	 *
	 * @param object $object
	 * @param mixed $jsonrpc_version JSON-RPC version (1.0 or 2.0) (string or number)
	 * @return boolean
	 */
	public static function handle($object, $jsonrpc_version = '1.0') {
		
		// version 1.0 or 2.0
		$jsonrpc_version = (float) $jsonrpc_version;
		if ($jsonrpc_version != 1 && $jsonrpc_version != 2) {
			throw new Exception('JSON-RPC version must be 1.0 or 2.0');
		}
		
		// checks if a JSON-RCP request has been received
		if (
			$_SERVER['REQUEST_METHOD'] != 'POST' || 
			empty($_SERVER['CONTENT_TYPE']) ||
			$_SERVER['CONTENT_TYPE'] != 'application/json'
			) {
			// This is not a JSON-RPC request
			return false;
		}
				
		// reads the input data
		$request = json_decode(file_get_contents('php://input'),true);
		
		// build the response object
		$response = array();
		
		// only version 2.0 includes requirement for response property of "jsonrpc"
		if ($jsonrpc_version == 2) {
			$response['jsonrpc'] = '2.0';
		}
		
		try {
			// executes the task on local object
			$result = @call_user_func_array(array($object,$request['method']),$request['params']);
			
			if ($result) {
				// if executes without errors
				$response['id'] = $request['id'];
				$response['result'] = $result;
				if ($jsonrpc_version == 1) {
					$response['error'] = NULL;
				}
			} else {
				// could not execute
				$response['id'] = $request['id'];
				$response['error'] = 'unknown method or incorrect parameters';
				if ($jsonrpc_version == 1) {
					$response['result'] = NULL;
				}
			}
		} catch (Exception $e) {
			// server threw an exception
			$response['id'] = $request['id'];
			$response['error'] = $e->getMessage();
			if ($jsonrpc_version == 1) {
				$response['result'] = NULL;
			}
		}
		
		// output the response
		if (
			isset($request['id']) &&
			$request['id'] != NULL &&
			$request['id'] != 'NULL' &&
			$request['id'] != 'null'
			) {
			// this is not a notification
			header('content-type: text/javascript');
			echo json_encode($response);
		}
		
		// return true if no exceptions
		return true;
	}
	
}


?>