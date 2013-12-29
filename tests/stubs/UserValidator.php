<?php namespace Humweb\Validation\Tests\Stubs;

use Humweb\Validation\Validation;

class UserValidator extends Validation {

	protected $rules = [
	    'default' => [
	    	'username' => 'required',
	        'password' => 'required',
	        'email' => 'required|email'
	    ],
	    'edit' => [
	    	'username' => '',
	    	'password' => '',
	        'email' => 'email'
	    ]
	];
}