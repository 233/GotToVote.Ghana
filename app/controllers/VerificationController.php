<?php

use Neutron\ReCaptcha\ReCaptcha;

class VerificationController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'VerificationController@getIndex');
	|
	*/


	public function getIndex()
	{
		if (Config::get('app.gtv.closed') == 1){
			return View::make('closed', array(
				'reg_no' => '',
				'message' => '<p>Enter your registration number first.</p>',
				'recaptcha' => ''
			));
		}
		// Initialize reCaptcha
		$recaptcha = ReCaptcha::create(Config::get('app.gtv.recaptcha.public_key'), Config::get('app.gtv.recaptcha.private_key'));

		return View::make('verify', array(
			'reg_no' => '',
			'message' => '<p>Enter your registration number first.</p>',
			'recaptcha' => $recaptcha
		));
	}

	public function verifyRegistration()
	{
		// Initialize variables
		$reg_no = Input::get('reg_no');

		// Check reCaptcha
		$recaptcha = ReCaptcha::create(Config::get('app.gtv.recaptcha.public_key'), Config::get('app.gtv.recaptcha.private_key'));
		$response = $recaptcha->checkAnswer($_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);

		if ($response->isValid())
		{
			// No voter number
			if ($reg_no == '') {
				$message = '<p class="text-danger">Enter your registration number first.</p>';
				return View::make('verify', array(
					'reg_no' => $reg_no,
					'message' => $message,
					'recaptcha' => $recaptcha
				));
			}

			// Validate registration no
			$validator = Validator::make(
			    array('reg_no' => $reg_no),
			    array('reg_no' => 'numeric|min:5')
			);
			if ($validator->fails())
			{
			    // The given data did not pass validation
			    $message = '<p class="text-danger">The registration number entered does not seem to be valid. Please check it and try again.</p>';
			    return View::make('verify', array(
			    	'reg_no' => $reg_no,
			    	'message' => $message,
			    	'recaptcha' => $recaptcha
			    ));
			}

			// Fetch from GTV VRC API
		    $message = $this->fetchFromAPI($reg_no);
		    return View::make('verify', array(
		    	'reg_no' => $reg_no,
		    	'message' => $message,
		    	'recaptcha' => $recaptcha
		    ));

		} else {
			// Captcha not valid
		    $message = '<p class="text-danger">Captcha not valid. Please try again.</p>';
		    return View::make('verify', array(
		    	'reg_no' => $reg_no,
		    	'message' => $message,
		    	'recaptcha' => $recaptcha
		    ));
		}

	}

	public function fetchFromAPI($reg_no)
	{
		$url = Config::get('app.gtv.api.url').'/web?reg_no='.$reg_no;
		$response = json_decode(file_get_contents($url));
		if ($response->success == 'false') {
			return '<p class="text-danger">'.$response->message.'</p>';
		}

		return '<p class="text-success">'.$response->message.'</p>';
	}

}
