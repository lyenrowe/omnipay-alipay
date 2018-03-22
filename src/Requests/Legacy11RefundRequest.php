<?php

namespace Omnipay\Alipay\Requests;

use Omnipay\Alipay\Responses\Legacy11RefundResponse;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\ResponseInterface;

/**
 * Class LegacyRefundRequest
 * @package Omnipay\Alipay\Requests
 * @link    https://doc.open.alipay.com/docs/doc.htm?treeId=66&articleId=103600&docType=1
 */
class Legacy11RefundRequest extends LegacyRefundRequest
{
    protected $service = 'refund_fastpay_by_platform_nopwd';


    protected $reqData;
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        if ($this->reqData) {
            return $this->reqData;
        }
        $this->setDefaults();

        $this->validate(
            'partner',
            '_input_charset',
            'refund_date',
            'batch_no',
            'refund_items'
        );

        $this->validateOne(
            'seller_user_id',
            'seller_email'
        );

        $this->setBatchNum(count($this->getRefundItems()));
        $this->setRefundDetail($this->getDetailData());

        $data = [
            'service'        => $this->service,
            'partner'        => $this->getPartner(),
            'batch_no'       => $this->getBatchNo(),
            'refund_date'    => $this->getRefundDate(),
            'batch_num'      => $this->getBatchNum(),
            'detail_data'    => $this->getDetailData(),
            'notify_url'     => $this->getNotifyUrl(),
            '_input_charset' => $this->getInputCharset()
        ];

        ksort($data);
        reset($data);

        $str = "";
        foreach ($data as $key => $value) {
            $str .= $key . "=" . $value . "&";
        }
        $arg = trim($str, "&");
        $data["sign"] = md5($arg . $this->getKey());
        $data["sign_type"] = 'MD5';

        $this->reqData = $data;
        return $data;
    }


    protected function setDefaults()
    {
        if (! $this->getRefundDate()) {
            $this->setRefundDate(date('Y-m-d H:i:s'));
        }

        if (! $this->getBatchNo()) {
            $this->setBatchNo(date('Ymd') . mt_rand(1000, 9999));
        }
    }


    /**
     * @return mixed
     */
    public function getRefundDate()
    {
        return $this->getParameter('refund_date');
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setRefundDate($value)
    {
        return $this->setParameter('refund_date', $value);
    }


    /**
     * @return mixed
     */
    public function getBatchNo()
    {
        return $this->getParameter('batch_no');
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setBatchNo($value)
    {
        return $this->setParameter('batch_no', $value);
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setBatchNum($value)
    {
        return $this->setParameter('batch_num', $value);
    }


    /**
     * @return mixed
     */
    public function getRefundItems()
    {
        return $this->getParameter('refund_items');
    }


    /**
     * @param $value
     *
     * @return $this
     */
    protected function setRefundDetail($value)
    {
        return $this->setParameter('refund_detail', $value);
    }


    protected function getDetailData()
    {
        $strings = [];

        foreach ($this->getRefundItems() as $item) {
            $item = (array) $item;

            if (! isset($item['out_trade_no'])) {
                throw new InvalidRequestException('The field `out_trade_no` is not exist in item');
            }

            if (! isset($item['amount'])) {
                throw new InvalidRequestException('The field `amount` is not exist in item');
            }

            if (! isset($item['reason'])) {
                throw new InvalidRequestException('The field `reason` is not exist in item');
            }

            $strings[] = implode('^', [$item['out_trade_no'], $item['amount'], $item['reason'] ?: '管理员退款']);
        }

        return implode('#', $strings);
    }


    /**
     * @return mixed
     */
    public function getPartner()
    {
        return $this->getParameter('partner');
    }


    /**
     * @return mixed
     */
    public function getNotifyUrl()
    {
        return $this->getParameter('notify_url');
    }


    /**
     * @return mixed
     */
    public function getBatchNum()
    {
        return $this->getParameter('batch_num');
    }


    /**
     * @return mixed
     */
    public function getInputCharset()
    {
        return $this->getParameter('_input_charset');
    }


    /**
     * @return mixed
     */
    public function getPaymentType()
    {
        return $this->getParameter('payment_type');
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setPaymentType($value)
    {
        return $this->setParameter('payment_type', $value);
    }


    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return ResponseInterface
     */
    public function sendData($data)
    {
        $url  = $this->getRequestUrl($data);

        $response = $this->httpClient->get($url, [], []);

        $resData = $response->getBody();
        if (! $resData) {
            throw new InvalidRequestException('alipay refund API cant reach:' . $url);
        }
        $data = (array)(new \SimpleXMLElement($resData));

        return $this->response = new Legacy11RefundResponse($this, $data);
    }


    /**
     * @param $data
     *
     * @return string
     */
    protected function getRequestUrl($data)
    {
        $queryParams = $data;

        ksort($queryParams);

        $url = sprintf('%s?%s', $this->getEndpoint(), http_build_query($queryParams));

        return $url;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setPartner($value)
    {
        return $this->setParameter('partner', $value);
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setInputCharset($value)
    {
        return $this->setParameter('_input_charset', $value);
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setNotifyUrl($value)
    {
        return $this->setParameter('notify_url', $value);
    }


    /**
     * @return mixed
     */
    public function getSellerEmail()
    {
        return $this->getParameter('seller_email');
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setSellerEmail($value)
    {
        return $this->setParameter('seller_email', $value);
    }


    /**
     * @return mixed
     */
    public function getSellerId()
    {
        return $this->getSellerUserId();
    }


    /**
     * @return mixed
     */
    public function getSellerUserId()
    {
        return $this->getParameter('seller_user_id');
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setSellerId($value)
    {
        return $this->setSellerUserId($value);
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setSellerUserId($value)
    {
        return $this->setParameter('seller_user_id', $value);
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setRefundItems($value)
    {
        return $this->setParameter('refund_items', $value);
    }


    /**
     * @return mixed
     */
    protected function getRefundDetail()
    {
        return $this->getParameter('refund_detail');
    }
}
