<?php namespace Humweb\Validation;

use Input, Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Contracts\MessageProviderInterface;

class Validation implements ValidationInterface, MessageProviderInterface
{
	protected $defaultScope = 'default';

	protected $tokenPrefix = '{';
	protected $tokenSuffix = '}';
    protected static $booted = array();
    protected static $validatorCache = array();

	/**
	 * Validators
	 *
	 * @var array
	 */
	private $validators = [];

	/**
	 * Attributes for validating
	 *
	 * @var array
	 */
	protected $attributes = [];


	/**
	 * Validation rules
	 *
	 * @var array
	 */
	protected $rules = [];

	/**
	 * Messages for validation rules
	 *
	 * @var array
	 */
	protected $messages = [];

	/**
	 * Validator scopes
	 *
	 * @var array
	 */
	protected $scopes = [];

	/**
	 * Bindings for rules
	 *
	 * @var array
	 */
	protected $bindings = [];

	/**
	 * Validator errors
	 *
	 * @var Illuminate\Support\MessageBag
	 */
	protected $errors;

	/**
	 * Creates a new Validator instance
	 *
	 * @param array 					$attributes
	 * @param string|array 				$scope
	 * @param ValidatorInstance|array 	$validator
	 */
	public function __construct($attributes = null, $scope = null, $validator = null)
	{
		$this->errors = new MessageBag;
		$this->validators[] = $this;

		if ($validator)
		{
			$this->extend($validator);
		}

		if ($scope)
		{
			$this->with($scope);
		}
        $this->bootIfNotBooted();
		$this->attributes = $attributes ?: Input::all();

	}

    /**
     * Check if the validator needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if ( ! isset(static::$booted[get_class($this)]))
        {
            static::$booted[get_class($this)] = true;

            static::boot();

        }
    }

    /**
     * The "booting" method of the validator
     *
     * @return void
     */
    protected function boot()
    {
        $class = get_called_class();

//        static::$validatorCache[$class] = array();
//
//        foreach (get_class_methods($class) as $method)
//        {
//            if (preg_match('/^validator(.+)Rule$/', $method, $matches))
//            {
//                static::$validatorCache[$class][] = lcfirst($matches[1]);
//            }
//        }
    }

	/**
	 * Static helper creates a new validator instance
	 *
	 * @param array 					$attributes
	 * @param string|array 				$scope
	 * @param ValidatorInstance|array 	$validator
	 * @return ValidationInterface
	 */
	public static function make($attributes = null, $scope = null, $validator = null)
	{
		return new static($attributes, $scope, $validator);
	}


	/**
	 * Add a validation scope
	 *
	 * @param array 	$scope
	 * @return ValidationInterface
	 */
	public function with($scope)
	{
		$scope = is_array($scope) ? $scope : [$scope];

		$this->scopes = array_merge($this->scopes, $scope);

		return $this;
	}

	/**
	 * Bind a replacement value to a placeholder in a rule
	 * You may also use '*' for the field to allow replacements globally
	 *
	 * @param  string 	$field
	 * @param  array 	$replacement
	 * @return ValidationInterface
	 */
	public function bind($field, array $replacement)
	{
		$this->bindings[$field] = $replacement;

		return $this;
	}


    /**
     * Extend current validator with more validators
     *
     * @param ValidationInterface|array $validator
     * @return $this
     */
	public function extend($validator)
	{
		$validator = is_array($validator) ? $validator : [$validator];

		$this->validators = array_merge($this->validators, $validator);

		return $this;
	}


    /**
     * Add new validation rules for the validator
     *
     * @param $ruleName
     * @param string|callable $validator
     * @param string $message
     *
     * @return $this
     */
	public function mixin($ruleName, $validator, $message = '')
	{
        if ( ! empty($message))
        {
            $this->messages[$ruleName] = $message;
        }
        Validator::extend($ruleName, $validator);

		return $this;
	}


	/**
	 * Perform validation
	 * 
	 * @return boolean
	 */
	public function passes()
	{

		if ( ! count($this->errors))
		{
			foreach ($this->validators as $validator)
			{
				if ( ! $validator->validate() and $validator !== $this)
				{
						$this->errors->merge($validator->errors()->getMessages());
				}
			}
		}

		return (count($this->errors) > 0) ? false : true;
	}


    /**
     * Internal validation for a single validator instance
     *
     * @param null $connection
     * @return boolean
     */
	protected function validate($connection = null)
	{
		$rules = $this->getRules();

        if (is_null($connection))
        {
            $validation = $this->getValidator($rules);
        }
        else {
            $vaidation = $this->getValidator($rules, $connection);
        }

		if ($validation->passes())
		{
			return true;
		}

		$this->errors = $validation->messages();

		return false;
	}

    protected function getValidator($rules, $connection = null)
    {
        if ( ! is_null($connection))
        {
            Validator::getPresenceVerifier()->setConnection($connection);
        }
        return Validator::make($this->attributes, $rules, $this->messages);
    }


	/**
	 * Return any errors.
	 * 
	 * @return Illuminate\Support\MessageBag
	 */
	public function errors()
	{
		if ( ! $this->errors) $this->passes();

		return $this->errors;
	}


	/**
	 * Return MessageBag instance
	 * 
	 * @return Illuminate\Support\MessageBag
	 */
	public function getMessageBag()
	{
		return $this->errors();
	}


	/**
	 * Get the validaton rules
	 * 
	 * @return array
	 */
	protected function getRules()
	{
		if ( ! $this->hasScope())
		{
			return $this->replaceBindings($this->rules);
		}

		// Set default rules
		$resultingRules = isset($this->rules[$this->getDefaultScope()]) ? $this->rules[$this->getDefaultScope()] : [];

		foreach ($this->scopes as $scope)
		{
			if ( ! isset($this->rules[$scope])) continue;

			$resultingRules = array_merge($resultingRules, $this->rules[$scope]);
		}

		return $this->replaceBindings($resultingRules);
	}


	/**
	 * Replace binding placeholders with actual values
	 * 
	 * @param  array 	$rules
	 * @return array
	 */
	private function replaceBindings($rules)
	{

		// If we have no bindings we can just return the rules
		if (empty($this->bindings))
		{
			return $rules;
		}

		// Get any global bindings to merge into all bindings
		// Global bindings allow binding values to all the rules
		$globalBinding = $this->getGlobalBindings();

		foreach ($rules as $field => $rule)
		{
			// Field specific bindings
			$bindings = $this->getBindings($field);

			// Merge global bindings
			if ( ! empty($globalBinding))
			{
				$bindings = $bindings + $globalBinding;
			}

			foreach ($bindings as $key => $value)
			{
				$token = $this->tokenPrefix.$key.$this->tokenSuffix;

				if (is_array($rule))
				{

					// Handle array type rule sets
					$rule = array_map(function($v) use ($value, $token) {

						return str_replace($token, $value, $v);

					}, $rule);

				}
				else {

					// Handle piped style rule set
					$rule = str_replace($token, $value, $rule);

				}
			}

			// Set field rule
			$rules[$field] = $rule;
		}

		return $rules;
	}


	/**
	 * Get a bound replacement by field name
	 * 
	 * @param  string $key
	 * @return array
	 */
	public function getBindings($key)
	{
		return $this->hasBindings($key) ? $this->bindings[$key] : [];
	}


	/**
	 * Get a bound replacement by field name
	 * 
	 * @param  string $key
	 * @return array
	 */
	public function hasBindings($key)
	{
		return isset($this->bindings[$key]);
	}


	/**
	 * Check if validator has a global binding
	 * 
	 * @return array
	 */
	public function getGlobalBindings()
	{
		return $this->hasGlobalBindings() ? $this->bindings['*'] : [];
	}


	/**
	 * Check if validator has a global binding
	 * 
	 * @return bool
	 */
	public function hasGlobalBindings()
	{
		return isset($this->bindings['*']);
	}


	/**
	 * Get all attributes
	 * 
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

    /**
     * Set data to be validated
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

	/**
	 * Retrieve the valiation scope.
	 * 
	 * @return array
	 */
	public function getScopes()
	{
		return $this->scopes;
	}


	/**
	 * Retrieve the valiation scope.
	 * 
	 * @return array
	 */
	public function getDefaultScope()
	{
		return $this->defaultScope;
	}


	/**
	 * Check if the current validation has a scope.
	 * 
	 * @return boolean
	 */
	public function hasScope()
	{
		return (count($this->getScopes()) or isset($this->rules[$this->getDefaultScope()]));
	}

}
