<?php namespace Humweb\Validation;

interface ValidationInterface {

	/**
	 * Static shorthand for creating a new validator.
	 * 
	 * @param  mixed 	$validator
	 * @return ValidationInterface
	 */
	public static function make($attributes = null, $scope = null, $validator = null);


	/**
	 * Add a validation scope.
	 * 
	 * @param array 	$scope
	 * @return ValidationInterface
	 */
	public function with($scope);


	/**
	 * Bind a replacement value to a placeholder in a rule.
	 * 
	 * @param  string 	$field
	 * @param  array 	$replacement
	 * @return ValidationInterface
	 */
	public function bind($field, array $replacement);


	/**
	 * Extend current validator with more validators
	 * 
	 * @param ValidationInterface|array $validator
	 */
	public function extend($validator);


	/**
	 * Perform validation
	 * 
	 * @return boolean
	 */
	public function passes();


	/**
	 * Return any errors.
	 * 
	 * @return Illuminate\Support\MessageBag
	 */
	public function errors();


	/**
	 * Get a bound replacement by field name
	 * 
	 * @param  string $key
	 * @return array
	 */
	public function getBindings($key);


	/**
	 * Check if validator has a global binding
	 * 
	 * @return array
	 */
	public function getGlobalBindings();


	/**
	 * Check if validator has a global binding
	 * 
	 * @return bool
	 */
	public function hasGlobalBindings();



	/**
	 * Get all attributes
	 * 
	 * @return array
	 */
	public function getAttributes();


	/**
	 * Retrieve the valiation scope.
	 * 
	 * @return array
	 */
	public function getScopes();


	/**
	 * Check if the current validation has a scope.
	 * 
	 * @return boolean
	 */
	function hasScope();

}