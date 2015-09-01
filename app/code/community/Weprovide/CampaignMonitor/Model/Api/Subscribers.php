<?php

/**
 * Class Weprovide_CampaignMonitor_Model_Api
 *
 * @author Lex Beelen <lex@weprovide.com>
 * @copyright Copyright (c) 2015, We/Provide http://www.weprovide.com
 */
class Weprovide_CampaignMonitor_Model_Api_Subscribers
{
    /**
     * @param int $storeId
     * @return CS_REST_Subscribers
     */
    protected function _api($storeId = 0){
        return new CS_REST_Subscribers(Mage::getModel('campaignmonitor/setting')->getSubscribeListApiKey($storeId),  array('api_key' => Mage::getModel('campaignmonitor/setting')->getApiKey($storeId)));
    }

    /**
     * @param $email
     * @param $subscribeId
     * @param $code
     * @param bool $customerId
     * @param int $storeId
     * @return bool
     * @throws Exception
     */
    public function subscribe($email, $subscribeId, $code, $customerId = false, $storeId = 0)
    {
        if(!$email || !$subscribeId || !$code) return false;

        $data = array();
        $data['EmailAddress'] = $email;

        $data['CustomFields'][] = array(
            'key' => Weprovide_CampaignMonitor_Model_Setting::CUSTOM_FIELD_SUBSCRIBER_ID,
            'Value' => $subscribeId
        );

        $data['CustomFields'][] = array(
            'key' => Weprovide_CampaignMonitor_Model_Setting::CUSTOM_FIELD_SUBSCRIBER_CONFIRM_CODE,
            'Value' => $code
        );

        if($customerId){
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if($customer->getEntityId()){
                $data['Name'] = $customer->getFirstname() . ' ' . $customer->getLastname();
            }
        }

        $data['Resubscribe'] = true;

        $result = $this->_api($storeId)->add($data);
        if(!$result->was_successful()) {
            throw new Exception($result->response->Message);
        }
    }

    /**
     * @param $email
     * @param int $storeId
     * @return bool
     * @throws Exception
     */
    public function unsubscribe($email, $storeId = 0)
    {
        if(!$email) return false;

        $result = $this->_api($storeId)->unsubscribe($email);
        if(!$result->was_successful()) {
            throw new Exception($result->response->Message);
        }
    }

    /**
     * @param bool $email
     * @param int $storeId
     * @return bool
     * @throws Exception
     */
    public function delete($email = false, $storeId = 0)
    {
        if (!$email) return false;

        $result = $this->_api($storeId)->delete($email);
        if(!$result->was_successful()) {
            throw new Exception($result->response->Message);
        }
    }

    /**
     * @param array $collection
     * @param int $storeId
     * @param $reSubscribe
     * @return bool
     * @throws Exception
     */
    public function import($collection = array(), $storeId = 0, $reSubscribe)
    {
        if ($collection->count() == 0) return false;
        $subscribers = array();
        $subscriberIds = array();

        foreach($collection as $item)
        {
            $subscriber = array();
            $subscriber['EmailAddress'] = $item->getSubscriberEmail();

            if ($item->getCustomerId()) {
                $subscriber['Name'] = $item->getCustomerFirstname() . ' ' . $item->getCustomerLastname();
            }

            $subscribers[] = $subscriber;
            $subscriberIds[] = $item->getSubscriberId();
        }

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('newsletter/subscriber');
        $query = "UPDATE {$table} SET campaign_monitor_imported = 1 WHERE subscriber_id IN ('" . implode("','", $subscriberIds) . "')";
        $writeConnection->query($query);

        if(!empty($subscribers)) {
            $result = $this->_api($storeId)->import($subscribers, $reSubscribe);
            if (!$result->was_successful()) {
                throw new Exception($result->response->Message);
            }
        }
    }

}