<?php
/**
 * Created by PhpStorm.
 * User: Stanislav Belichenko, E-mail: s.belichenko@studio-sold.ru, Skype: s.belichenko.sold
 * Company: «SOLD», E-mail: studio@studio-sold.ru
 * Date: 15.10.2017
 * Time: 12:37
 */

namespace Bitrix24\CRM;

use Bitrix24\Bitrix24IncomingWebhook;

class LeadIncWebhook extends Bitrix24IncomingWebhook {
	/**
	 * Add a new lead to CRM
	 *
	 * @param array $fields array of fields
	 * @param array $params Set of parameters. REGISTER_SONET_EVENT - performs registration of a change event in a lead
	 *                      in the Activity Stream. The lead's Responsible person will also receive notification.
	 *
	 * @link http://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_add.php
	 * @return array
	 */
	public function add( $fields = array(), $params = array() ) {
		$fullResult = $this->client->call(
			'crm.lead.add',
			array(
				'fields' => $fields,
				'params' => $params
			)
		);

		return $fullResult;
	}
}