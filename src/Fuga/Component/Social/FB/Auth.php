<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 10/02/16
 * Time: 22:26
 */

namespace Fuga\Component\Social\FB;


class Auth
{

	private $container;
	private $appId;
	private $appSharedSecret;
	private $redirectURI;
	private $url;

	public function __construct(\Fuga\Component\Container $container, $appId, $appSharedSecret, $redirectURI, $url)
	{
		$this->container = $container;
		$this->appId = $appId;
		$this->appSharedSecret = $appSharedSecret;
		$this->redirectURI = $redirectURI;
		$this->url = $url;
	}

//	public function member()
//	{
//		$fb = new \Facebook\Facebook([
//			'app_id' => $this->appId,
//			'app_secret' => $this->appSharedSecret,
//			'default_graph_version' => 'v2.5',
//		]);
//
//		$helper = $fb->getJavaScriptHelper();
//
//		try {
//			$accessToken = $helper->getAccessToken();
//		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
//			// When Graph returns an error
//			return array(
//				'status' => false,
//				'message' => 'Graph returned an error: ' . $e->getMessage(),
//			);
//		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
//			// When validation fails or other local issues
//			return array(
//				'status' => false,
//				'message' => 'Facebook SDK returned an error: ' . $e->getMessage(),
//			);
//		}
//
//		if (! isset($accessToken)) {
//			return array(
//				'status' => false,
//				'message' => 'No cookie set or no OAuth data could be obtained from cookie.',
//			);
//		}
//
////		var_dump($accessToken->getValue());
//
//		$this->container->get('session')->set('gallery_user', (string) $accessToken);
//
//		return array(
//			'status' => true,
//			'message' => 'Facebook User Logged In',
//			'access' => (string) $accessToken,
//		);
//	}

	public function verify()
	{
		$result = array(
			'status' => false,
			'user' => null,
			'user_local' => null,
			'message' => 'Not authenticated',
			'register' => false,
		);

		if (isset($_GET['code'])) {

			$params = array(
				'client_id'     => $this->appId,
				'redirect_uri'  => $this->redirectURI,
				'client_secret' => $this->appSharedSecret,
				'code'          => $_GET['code'],
			);

			try {

				$fb = new \Facebook\Facebook([
					'app_id' => $this->appId,
					'app_secret' => $this->appSharedSecret,
					'default_graph_version' => 'v2.5',
				]);

				$helper = $fb->getRedirectLoginHelper();

				try {
					$accessToken = $helper->getAccessToken();
				} catch(\Facebook\Exceptions\FacebookResponseException $e) {
					// When Graph returns an error
					echo 'Graph returned an error: ' . $e->getMessage();
					exit;
				} catch(\Facebook\Exceptions\FacebookSDKException $e) {
					// When validation fails or other local issues
					echo 'Facebook SDK returned an error: ' . $e->getMessage();
					exit;
				}

				if (! isset($accessToken)) {
					if ($helper->getError()) {
						header('HTTP/1.0 401 Unauthorized');
						echo "Error: " . $helper->getError() . "\n";
						echo "Error Code: " . $helper->getErrorCode() . "\n";
						echo "Error Reason: " . $helper->getErrorReason() . "\n";
						echo "Error Description: " . $helper->getErrorDescription() . "\n";
					} else {
						header('HTTP/1.0 400 Bad Request');
						echo 'Bad request';
					}
					exit;
				}

				if (isset($accessToken)) {

					try {
						// Returns a `Facebook\FacebookResponse` object
						$response = $fb->get('/me?fields=id,name,first_name,last_name,email,birthday', $accessToken);
					} catch(\Facebook\Exceptions\FacebookResponseException $e) {
						echo 'Graph returned an error: ' . $e->getMessage();
						exit;
					} catch(\Facebook\Exceptions\FacebookSDKException $e) {
						echo 'Facebook SDK returned an error: ' . $e->getMessage();
						exit;
					}

					$userInfo = $response->getGraphUser();

					if (isset($userInfo['id'])) {
						$result['status'] = true;
						$result['message'] = "Success";

						$network = 'fb';
						$age = '';

						$email = $userInfo->getEmail();

						if (!$email) {
							$email = $userInfo->getId().'@facebook.com';
						}

//						$birthday = $userInfo->getBirthday();
//
//						if ($birthday) {
//							$year = $birthday->format('Y');
//							$age = date('Y') - $year;
//						}

						$result['user'] = $userInfo;

						$localUser = $this->container->getItem('gallery_user', 'email="'.$email.'"');
						if ($localUser) {
							$this->container->updateItem(
								'gallery_user',
								array(
									'social_'.$network => $userInfo->getId()
								),
								array('id' => $localUser['id'])
							);

							$localUser = $this->container->getItem('gallery_user', 'email="'.$email.'"');
							unset($localUser['password']);

						} else {

							$password = $this->container->get('util')->genKey(8);

							$this->container->get('log')->addError(json_encode(array(
								'email' => $email,
								'password' => md5($password),
								'name' => $userInfo->getFirstName(),
								'lastname' => $userInfo->getLastName(),
								'age' => $age,
								'social_'.$network => $userInfo->getId(),
							)));

							$userId = $this->container->addItem('gallery_user', array(
								'email' => $email,
								'password' => md5($password),
								'name' => $userInfo->getFirstName(),
								'lastname' => $userInfo->getLastName(),
								'age' => $age,
								'social_'.$network => $userInfo->getId(),
							));
							$localUser = $this->container->getItem('gallery_user', 'email="'.$email.'"');
							$result['register'] = true;
						}

						$result['user_local'] = $localUser;
					}
				}
			} catch (\Exception $e) {
				$result['message'] = $e->getMessage();
				$this->container->get('log')->addError('Catch FB AUTH Error: '.$e->getMessage());
			}
		}

		return $result;
	}

	public function url()
	{
		$fb = new \Facebook\Facebook([
			'app_id' => $this->appId, // Replace {app-id} with your app id
			'app_secret' => $this->appSharedSecret,
			'default_graph_version' => 'v2.5',
		]);

		$helper = $fb->getRedirectLoginHelper();

		$permissions = array('email'); // Optional permissions
		$loginUrl = $helper->getLoginUrl($this->redirectURI, $permissions);

		return urldecode($loginUrl);
	}

}





