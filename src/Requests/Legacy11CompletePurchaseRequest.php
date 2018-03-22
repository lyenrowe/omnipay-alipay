<?php

namespace Omnipay\Alipay\Requests;

use Omnipay\Alipay\Responses\LegacyCompletePurchaseResponse;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\ResponseInterface;

class Legacy11CompletePurchaseRequest extends AbstractLegacyRequest
{

    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->getParams();
    }


    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->getParameter('params');
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
        $finalData = (array) (new \SimpleXMLElement($data["notify_data"]));
        if (! $finalData) {
            throw new InvalidRequestException('The `notify_data` is empty');
        }
        if ((strval($finalData['trade_status']) != "TRADE_FINISHED" && strval($finalData['trade_status']) != 'TRADE_SUCCESS')) {
            throw new InvalidRequestException('The `trade_status` is ' . strval($finalData['trade_status']));
        }
        $notify_data = "notify_data=" . $data["notify_data"];
        $sign = $data["sign"];
        $signType = $data["sign_type"];
        $this->verifySignature($notify_data, $sign, $signType);

        return $this->response = new LegacyCompletePurchaseResponse($this, $finalData);
    }


    protected function verifySignature($data, $sign, $signType)
    {
        if ($signType !== 'RSA') {
            throw new InvalidRequestException('The `sign_type` is invalid');
        }

        if (! $this->getAlipayPublicKey()) {
            throw new InvalidRequestException('The `alipay_public_key` is required for `RSA` sign_type');
        }

        //转换为openssl格式密钥
        $res = openssl_get_publickey($this->getAlipayPublicKey());
        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        if (! $result) {
            throw new InvalidRequestException('The signature is not match');
        }

        return $result;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setParams($value)
    {
        return $this->setParameter('params', $value);
    }
}
