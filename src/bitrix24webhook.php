<?php
/**
 * Created by PhpStorm.
 * User: Stanislav Belichenko, E-mail: s.belichenko@studio-sold.ru, Skype: s.belichenko.sold
 * Company: «SOLD», E-mail: studio@studio-sold.ru
 * Date: 15.10.2017
 * Time: 12:28
 */

namespace Bitrix24;

use Bitrix24\Contracts\iBitrix24Webhook;

use Bitrix24\Exceptions\Bitrix24Exception;
use Bitrix24\Exceptions\Bitrix24IoException;
use Bitrix24\Exceptions\Bitrix24PortalDeletedException;
use Bitrix24\Exceptions\Bitrix24BadGatewayException;
use Bitrix24\Exceptions\Bitrix24EmptyResponseException;


class Bitrix24Webhook implements iBitrix24Webhook {
	/**
	 * @var string SDK version
	 */
	const VERSION = '1.0';

	/**
	 * @var string domain
	 */
	protected $domain;

	/**
	 * @var string application secret
	 */
	protected $applicationSecret;

	/**
	 * @var array, contain all api-method parameters, will be available after call method
	 */
	protected $methodParameters;

	/**
	 * @var array custom options for cURL
	 */
	protected $customCurlOptions;

	/**
	 * @var integer CURL request count retries
	 */
	protected $retriesToConnectCount;

	/**
	 * @var array raw request, contain all cURL options array and API query
	 */
	protected $rawRequest;

	/**
	 * @var array request info data structure акщь curl_getinfo function
	 */
	protected $requestInfo;

	/**
	 * @var integer retries to connect timeout in microseconds
	 */
	protected $retriesToConnectTimeout;

	/**
	 * @var array raw response from bitrix24
	 */
	protected $rawResponse;

	/**
	 * @param       $methodName
	 * @param array $additionalParameters
	 *
	 * @return mixed
	 * @throws Bitrix24Exception
	 */
	public function call( $methodName, array $additionalParameters = array() ) {
		$result = $this->_call( $methodName, $additionalParameters );

		return $result;
	}

	/**
	 * Set domain
	 *
	 * @param $domain
	 *
	 * @throws Bitrix24Exception
	 *
	 * @return true;
	 */
	public function setDomain( $domain ) {
		if ( '' === $domain ) {
			throw new Bitrix24Exception( 'domain is empty' );
		}
		$this->domain = $domain;

		return true;
	}

	/**
	 * Set application secret
	 *
	 * @param string $applicationSecret
	 *
	 * @throws Bitrix24Exception
	 *
	 * @return true;
	 */
	public function setApplicationSecret( $applicationSecret ) {
		if ( '' === $applicationSecret ) {
			throw new Bitrix24Exception( 'application secret is empty' );
		}
		$this->applicationSecret = $applicationSecret;
		return true;
	}

	/**
	 * Get domain
	 *
	 * @return string | null
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * Get application secret
	 *
	 * @return string
	 */
	public function getApplicationSecret() {
		return $this->applicationSecret;
	}

	/**
	 * @param       $methodName
	 * @param array $additionalParameters
	 *
	 * @return mixed
	 * @throws Bitrix24Exception
	 */
	protected function _call( $methodName, array $additionalParameters = array() ) {
		if ( null === $this->getDomain() ) {
			throw new Bitrix24Exception( 'domain not found, you must call setDomain method before' );
		}
		if ( null === $this->getAccessToken() ) {
			throw new Bitrix24Exception( 'access token not found, you must call setAccessToken method before' );
		}
		if ( '' === $methodName ) {
			throw new Bitrix24Exception( 'method name not found, you must set method name' );
		}

		$url = 'https://' . $this->domain . '/rest/1/' . $this->accessToken . '/' . $methodName;

		// save method parameters for debug
		$this->methodParameters = $additionalParameters;

		$requestResult = $this->executeRequest( $url, $additionalParameters );

		return $requestResult;
	}

	/**
	 * @param       $url
	 * @param array $additionalParameters
	 *
	 * @return mixed
	 * @throws Bitrix24Exception
	 * @throws Bitrix24IoException
	 */
	protected function executeRequest( $url, array $additionalParameters = array() ) {
		$retryableErrorCodes = array(
			CURLE_COULDNT_RESOLVE_HOST,
			CURLE_COULDNT_CONNECT,
			CURLE_HTTP_NOT_FOUND,
			CURLE_READ_ERROR,
			CURLE_OPERATION_TIMEOUTED,
			CURLE_HTTP_POST_ERROR,
			CURLE_SSL_CONNECT_ERROR
		);

		$curlOptions = array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLINFO_HEADER_OUT    => true,
			CURLOPT_VERBOSE        => true,
			CURLOPT_CONNECTTIMEOUT => 65,
			CURLOPT_TIMEOUT        => 70,
			CURLOPT_USERAGENT      => strtolower( __CLASS__ . '-PHP-SDK/v' . self::VERSION ),
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => http_build_query( $additionalParameters ),
			CURLOPT_URL            => $url
		);

		if ( is_array( $this->customCurlOptions ) ) {
			foreach ( $this->customCurlOptions as $customCurlOptionKey => $customCurlOptionValue ) {
				$curlOptions[ $customCurlOptionKey ] = $customCurlOptionValue;
			}
		}

		$this->rawRequest = $curlOptions;
		$curl             = curl_init();
		curl_setopt_array( $curl, $curlOptions );

		$curlResult = false;
		$retriesCnt = $this->retriesToConnectCount;
		while ( $retriesCnt -- ) {
			$curlResult = curl_exec( $curl );
			// handling network I/O errors
			if ( false === $curlResult ) {
				$curlErrorNumber = curl_errno( $curl );
				$errorMsg        = sprintf( 'in try[%s] cURL error (code %s): %s' . PHP_EOL, $retriesCnt,
					$curlErrorNumber,
					curl_error( $curl ) );
				if ( false === in_array( $curlErrorNumber, $retryableErrorCodes, true ) || ! $retriesCnt ) {
					curl_close( $curl );
					throw new Bitrix24IoException( $errorMsg );
				}
				usleep( $this->getRetriesToConnectTimeout() );
				continue;
			}
			$this->requestInfo = curl_getinfo( $curl );
			$this->rawResponse = $curlResult;
			curl_close( $curl );
			break;
		}

		// handling URI level resource errors
		switch ( $this->requestInfo['http_code'] ) {
			case 403:
				$errorMsg = sprintf( 'portal [%s] deleted, query aborted', $this->getDomain() );
				throw new Bitrix24PortalDeletedException( $errorMsg );
				break;

			case 502:
				$errorMsg = sprintf( 'bad gateway to portal [%s]', $this->getDomain() );
				throw new Bitrix24BadGatewayException( $errorMsg );
				break;
		}

		// handling server-side API errors: empty response from bitrix24 portal
		if ( $curlResult === '' ) {
			$errorMsg = sprintf( 'empty response from portal [%s]', $this->getDomain() );
			throw new Bitrix24EmptyResponseException( $errorMsg );
		}

		// handling json_decode errors
		$jsonResult = json_decode( $curlResult, true );
		unset( $curlResult );
		$jsonErrorCode = json_last_error();
		if ( null === $jsonResult && ( JSON_ERROR_NONE !== $jsonErrorCode ) ) {
			/**
			 * @todo add function json_last_error_msg()
			 */
			$errorMsg = 'fatal error in function json_decode.' . PHP_EOL . 'Error code: ' . $jsonErrorCode . PHP_EOL;
			throw new Bitrix24Exception( $errorMsg );
		}

		return $jsonResult;
	}

	/**
	 * get retries to connect timeout in microseconds
	 *
	 * @return mixed
	 */
	public function getRetriesToConnectTimeout() {
		return $this->retriesToConnectTimeout;
	}
}