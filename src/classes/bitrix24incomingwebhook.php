<?php
/**
 * Created by PhpStorm.
 * User: Stanislav Belichenko, E-mail: s.belichenko@studio-sold.ru, Skype: s.belichenko.sold
 * Company: «SOLD», E-mail: studio@studio-sold.ru
 * Date: 15.10.2017
 * Time: 12:33
 */

namespace Bitrix24;

use Bitrix24\Contracts\iBitrix24Webhook;

abstract class Bitrix24IncomingWebhook {
	/**
	 * @var iBitrix24Webhook
	 */
	public $client = null;

	/**
	 * @param $client iBitrix24Webhook
	 */
	public function __construct( iBitrix24Webhook $client ) {
		$this->client = $client;
	}
}