<?php
/**
 * Created by PhpStorm.
 * User: Stanislav Belichenko, E-mail: s.belichenko@studio-sold.ru, Skype: s.belichenko.sold
 * Company: «SOLD», E-mail: studio@studio-sold.ru
 * Date: 15.10.2017
 * Time: 12:25
 */

namespace Bitrix24\Contracts;


interface iBitrix24Webhook {
	public function call( $methodName, array $additionalParameters = array() );
}