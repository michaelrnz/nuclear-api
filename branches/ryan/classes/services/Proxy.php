<?php
/*
 * MethodProxy
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Acts as a manager for routing method calls
 */

class Proxy {


	const METHOD_INTERFACE		= "IMethod";
	const RESPONSE_INTERFACE	= "IResponse";
	const METHOD_ERROR			= "Method does not implement IMethod";
	const RESPONSE_ERROR		= "Response does not implement IResponse";


	/**
	 * Rest mode (get/post) default to GET
	 * @var int $restMode
	 */
	protected $restMode;


	/**
	 * Error
	 */
	protected $error;


	/**
	 * Constructor
	 * 
	 * @return void
	 */
	public function __construct () {}


	/**
	 * Magic call, pass parameters to a method
	 *
	 * @return Response
	 */
	public function __call ($name, $args) {

		try {

			// Determine the method class
			$className	= $this->ClassName(
								$this->MethodName(
									$this->RestPrefix(
										$this->restMode), $name));

			// Verify Method fits interface constraint
			if (in_array(self::METHOD_INTERFACE, class_implements($className))) {

				// Instantiate new Method class
				$method		= new $className();

				// Set the type hint and terminal; execute for response
				if (count($args)>0) {
					$method->Prepare($request);
				}

				$response	= $method->Execute();

				// Verify Response fits interface constraint
				$interface = self::RESPONSE_INTERFACE;
				if ($response instanceof $interface) {

					return $response;

				} else {

					throw new Exception(self::RESPONSE_ERROR);
				}

			} else {

				throw new Exception(self::METHOD_ERROR);
			}

		} catch (Exception $e) {

			$this->error = $e;
		}

		return null;
	}


	/**
	 * Change proxy mode to GET
	 */
	public function Get () {

		$this->restMode = Request::GET;
		return $this;
	}


	/**
	 * Change proxy mode to POST
	 */
	public function Post () {

		$this->restMode = Request::POST;
		return $this;
	}


	/**
	 * Change proxy mode to PUT
	 */
	public function Put () {

		$this->restMode = Request::PUT;
		return $this;
	}


	/**
	 * Change proxy mode to DELETE
	 */
	public function Delete () {

		$this->restMode = Request::DELETE;
		return $this;
	}


	/**
	 * Change proxy mode to REST
	 *
	 * @param int $restMode
	 * @return Proxy
	 */
	public function SetMode ($restMode) {

		$this->restMode = $restMode;
		return $this;
	}


	/**
	 * Has Error
	 *
	 * @return bool
	 */
	public function HasError () {
		return $this->error instanceof Exception;
	}


	/**
	 * Retrieve Error
	 *
	 * @return Exception
	 */
	public function Error () {
		return $this->error;
	}


	/**
	 * Get the REST prefix for rest mode
	 *
	 * @param int $restMode
	 * @return string
	 */
	protected function RestPrefix ($restMode) {

		switch ($restMode) {

			case Request::POST:
				return "post";

			case Request::PUT:
				return "put";

			case Request::DELETE:
				return "delete";

			default:
				return "get";
		}
	}


	/**
	 * Generate the method name (class name)
	 * from rest prefix and name
	 *
	 * @param string $prefix
	 * @param string $name
	 * @return string
	 */
	protected function MethodName ($prefix, $name) {

		return str_replace(' ', '',
					ucwords(
						preg_replace('/[\/\-]/', ' ', "{$prefix}/{$name}")));
	}
	

	/**
	 * Load and validate that Method class exists
	 *
	 * @param string $methodName
	 * @return string
	 */
	protected function ClassName ($methodName) {

		// allow class_exists to hit the EnvironmentIndex
		if (class_exists($methodName)) {
			return $methodName;
		}

		return false;
	}


}
