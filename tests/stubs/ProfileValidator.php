<?php namespace Humweb\Validation\Tests\Stubs;

use Humweb\Validation\Validation;

class ProfileValidator extends Validation {

	protected $rules = [
	    'default' => [
	        'first_name' => 'required',
	        'last_name' => 'required'
	    ]
	];
}