<?php
/**
 * Created by PhpStorm.
 * User: Stanislav Belichenko, E-mail: s.belichenko@studio-sold.ru, Skype: s.belichenko.sold
 * Company: «SOLD», E-mail: studio@studio-sold.ru
 * Date: 19.10.2017
 * Time: 14:05
 */

namespace Bitrix24\User;

use Bitrix24\Bitrix24IncomingWebhook;
use Bitrix24\Exceptions\Bitrix24Exception;

class UserWebhook extends Bitrix24IncomingWebhook {
	/**
	 * Get list of users
	 * @link http://dev.1c-bitrix.ru/rest_help/users/user_get.php
	 * @throws Bitrix24Exception
	 *
	 * @param $SORT   - field name to sort by them
	 * @param $ORDER  - sort direction? must be set to ASC or DESC
	 * @param $FILTER - list of fields user entity to filter result
	 *
	 * @return array
	 */
	public function get( $SORT, $ORDER, $FILTER ) {
		$result = $this->client->call( 'user.get',
			array(
				'SORT'   => $SORT,
				'ORDER'  => $ORDER,
				'FILTER' => $FILTER
			)
		);

		return $result;
	}
}