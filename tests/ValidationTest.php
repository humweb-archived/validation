<?php namespace Humweb\Validation\Tests;

use Orchestra\Testbench\TestCase;
use Humweb\Validation\Tests\Stubs\UserValidator;
use Humweb\Validation\Tests\Stubs\ProfileValidator;

class ValidationTest extends TestCase {


	public function testExtendedFailureValidation()
	{
		$userData = [
			'username' => 'johndoe',
			'email' => 'johndoeemail.com',
			'password' => '123456'
		];

		$profileData = [
			'first_name' => 'john'
		];

		$profileValidator = ProfileValidator::make($profileData);
		$userValidator = UserValidator::make($userData)
			->extend($profileValidator)
			->bind('*', ['id' => 33]);

		$userValidator->passes();

		$expects = 2;
		$result = count($userValidator->errors());

		$this->assertEquals($expects, $result);

	}


	public function testExtendedPassesValidation()
	{
		$userData = [
			'username' => 'johndoe',
			'email' => 'johndoe@email.com',
			'password' => '123456'
		];

		$profileData = [
			'first_name' => 'john',
			'last_name' => 'doe'
		];
		$profileValidator = ProfileValidator::make($profileData);
		$userValidator = UserValidator::make($userData)
			->extend($profileValidator)
			->bind('*', ['id' => 33]);

		$userValidator->passes();

		$expects = 0;
		$result = count($userValidator->errors());

		$this->assertEquals($expects, $result);

	}


	public function testScopedWithValidation()
	{
		$userData = [
			'username' => 'johndoe',
			'email' => 'johndoe@email.com'
		];

		$profileData = [
			'first_name' => 'john',
			'last_name' => 'doe'
		];
		$profileValidator = ProfileValidator::make($profileData);
		$userValidator = UserValidator::make($userData)
			->with('edit')
			->extend($profileValidator)
			->bind('*', ['id' => 33]);

		$userValidator->passes();

		$expects = 0;
		$result = count($userValidator->errors());

		$this->assertEquals($expects, $result);

	}

}