<?php
/**
 * Created by PhpStorm.
 * User: yyy
 * Date: 17/4/25
 * Time: 下午9:03
 */
namespace Common\Service;
class PayService extends BaseService{

    const AlipayPubKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkbQ7g/rnaHhBuGeJMxZGjhoEsLbL9UQjnUjN2Uk3k9LuLuLNXJ13Os4TY8IoYiAKH5FUwy8ZIbbTaQE0txcvtBEhLquvV2PG0Nd9IohPQs4zEN64HJO236VGdzrzTrPF3MKfPrWkqPc1d6O1ZBfDKKe1RJkV1NuWxpMNG1l+xRweEck5c6drD6JpQSG6e72SuguQurVTCI8j98tUjzbv4mruTzMjMpIlDg/hx0RPexfFKHxkphZGV5tM5u13ReONZpAAfu12ia55/w0PxW04CosqXo97cNGaZXn/eaMp73+pMcj/x+tMWKWJiWbsmBG2CvccnUSdRFWTdfs7WnCZzQIDAQAB';
    const AlipayPriKey = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCYzcal5e4U1xQQmM1oQqShk/FWe5BTsGhl/1TLumqDCRgcY+z8wVytBe1P8Ga4JiRvZj/U94gFHo5LOrJrQh95tk2IuvuNVjFPzpLlnWf1dc82s8yXOcyxSzAAASSfJJeGJjQldwh0AVZIgU+csJHgBVTtvgulQjTwW45b+tmbt7P49b6wZJi+NcYJImXPbguc+D9ukqbyffFY2eJ9RJIp7o3zQsMW4mnxyMa0uVQU5UPbvqnWYSF3d2n2ENz5BlUQNpR2F6JnGuyW6tHysTG2M7T3LTapbQiwqkAFn6MMPFmwte/kGIzh2ArLdRPvLli9jR2/j4aGtytOqFXFQRsjAgMBAAECggEAG1vMk2uFoymXKBmTvXUhMOTiMw/QmYteRdTfg9KOu4NnU734cpDUXQ5QnR135sS9hUyTZTgknHYKGCIcS+P86rffTfncjEPAdH+SAZabRHGhdjPfD7yDj8Lch0OtIOlWT+iLMaIMW2jZ4AV3EaDznV6XBDIgt6gQ/nAGHyXczGpiqyxQr2WLOE5s4b22n6qmcw+4fuKgJqJagwu93slJeeeCq15+fF/rHxNeoIyVUF4C+z6WM0syaKL2aM8Vw68obSCmKE+At5TuJQluWcg/ilBKSTryg98wEiNEC74erojhfpVHq6MuOEEFGmOfNyAbl4kc7DhYLzooVl0aIxYiUQKBgQDYjfiHCTibbrMDHtrxCUWfHMirZmIPukVHN3x5qP4wXdtYkC/Tzr6pQ/Ji2Rl52XXzeO8UyE8LFAFJgxGLpTiEmy123HLOHxcB2hOFJY5TD3Yk93tVdjipaEClDMTP6AcfLz7/EcSkYLRYpVPSgAfdgK74KDcpG1bFN2rue5zatwKBgQC0oxSyDWap4shrNRknE1yx11zWFJnbTfvQXeEp8PX1g/hnolFVlZS7VYMDH5RhNvSK3yIbJP3ZCvxSXxFTctnmAY6QSu+NOMBKVxiW89OO4FdFhrb9i07QyhAyDeH4siiS9prNXvDzHqyiD5lYPQed2OMccU/6d5YqTQ17h5iG9QKBgQDL+07yw7jSkD+G3PWWvgkai15qNRKBhg/juVxCrPBiVsZacdbbSI9HmX0jpyPcJv53zJ5HkTcDVGCyAgfw5jyKjDETGSv7BEYDtItWi724d4Pt5kACjE1rJYxe69wnioPK2BIa6X206HJ4XaLLUVYXSzOFBTyQN/RP6JeM9FsXmQKBgFf6IYdCraDKWlCUsOZuMLRRLt75c6HzDlUClDqoKDLmjqJy1Og5DRJcaI2p4MukR2AnouXTk2sVRaUctkSNaID0eyndxWRjoovSdaB3qq8opnivTwqXwdBAybiHOGq24roJL4Yc2n+ejff0Xvwx5TbEvoBI0+oqOlHp60oh1XDNAoGBALkHBlPyrjnVHRzM6YD9lwO/3r3ih2yEeqrhjYBesh5IdvfTyKpjlheouLryhn2aSr8TEsX+Cmm615gsjJqXtSKM9Djbyc5P99ueP3NkiyHuSXJ1abJwHhETEego3fBw6fMaqCiYbEAORY8MRwEIMXL9rEbs8yFQ4RuGoDFs2sDv';
    const AlipayAppId = '2017051507245387';
    const AlipayAppIdTest = '2016080600178402';
    const AlipayPubKeyTest = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsiYC6F3Qps+MxnWumT9JvzDiFbwa5d8WrMdwKdcD27TQddZVuxddSpdBf6x7KwbHsD1lxgowkEONqukUPJw6pGjJd40Zsa8XoeORFGLsH4cWSZSO6moWZ+1F+xkuexurtv+4UIYaE5aHdfzzP0f+nOAVVkkzAl+kma8Z5i0c1enblUlq6qclDEFNKj1oJNS3rK/+nUKb+DD2qKz8dv/bMm4uit/eJxH4SsiO06CybUw+B7HHp3yi8pb/RKPtO28rXNbFYzFeNrwfYss6SM5VyI/kbSZ0uM4JLOA9nAWoxgaA40amftjUVBDC0LvLacO8pXeAQceT4finYWFedJC2dQIDAQAB';
    public function add_one($data) {
        $NfPay = D('NfPay');
        if ($NfPay->add($data)) {

            return result(TRUE, '', $NfPay->getLastInsID());
        } else {
            return result(FALSE, '网络繁忙~');
        }
    }

    public function get_info_by_id($id) {
        $NfPay = D('NfPay');
        return $NfPay->where('id = ' . $id)->find();
    }

    public function get_info_by_no($no) {
        $NfPay = D('NfPay');
        return $NfPay->where('pay_no = "' . $no . '"')->find();
    }

    public function get_info_by_oid($oid) {
        $NfPay = D('NfPay');
        return $NfPay->where('order_id = ' . $oid)->find();
    }

    public function update_by_id($id, $data) {

        if (!$id) {
            return result(FALSE, 'id不能为空');
        }

        $NfPay = D('NfPay');

        if ($NfPay->where('id=' . $id)->save($data)) {
            return result(TRUE);
        } else {
            return result(FALSE, $NfPay->getError());
        }
    }

    public function update_by_ids($ids, $data) {
        if (!check_num_ids($ids)) {
            return result(FALSE, 'ids不能为空');
        }
        $NfPay = D('NfPay');
        $ret = $NfPay->where('id in ('. join(',', $ids) .')')->save($data);

        if ($ret) {
            return result(TRUE);
        } else {
            return result(FALSE, $NfPay->getError());
        }
    }

    public function del_by_id($id) {
        if (!check_num_ids([$id])) {
            return result(FALSE, 'id不合法~');
        }
        $NfPay = D('NfPay');
        $ret = $NfPay->where('id=' . $id)->save(['status'=>\Common\Model\NfPayModel::STATUS_DELETE]);
        if ($ret) {
            return result(TRUE);
        } else {
            return result(FALSE, '网络繁忙~');
        }
    }


    public function add_batch($data) {
        $NfPay = D('NfPay');
        if ($NfPay->addAll($data)) {
            return result(TRUE);
        } else {
            return result(FALSE, '批量插入失败');
        }
    }

    public function get_pay_no($uid) {
        return 'T'.$uid.getMillisecond().mt_rand(0,9).mt_rand(0,9);
    }

    public function create_by_order($order) {
        if (!$order) {
            return result(FALSE, '订单不存在~');
        }

        if ($pay = $this->get_info_by_oid($order['id'])) {
            if ($pay['status'] != \Common\Model\NfPayModel::STATUS_SUBMIT) {
                return result(FALSE, '该订单无法支付~');
            }
            return result(TRUE, '订单已创建支付', $pay);
        }

        $data = [];
        $data['pay_no'] = $this->get_pay_no($order['uid']);
        $data['pay_agent'] = \Common\Model\NfPayModel::PAY_AGENT_ALIPAY;
        $data['uid'] = $order['uid'];
        $data['order_id'] = $order['id'];
        $data['sum'] = $order['sum'];
        $data['create_time'] = current_date();

        $ret = $this->add_one($data);

        if (!$ret->success) {
            return result(FALSE, $ret->message);
        }
        $data['id'] = $ret->data;

        return result(TRUE, '创建成功', $data);

    }

    public function is_available_complete($no) {
        $pay = $this->get_info_by_no($no);
        if (!$pay) {
            return FALSE;
        }
        if ($pay['status'] != \Common\Model\NfPayModel::STATUS_SUBMIT) {
            return FALSE;
        }
        return $pay;

    }

    public function complete($no) {
        if (!$no) {
            return result(FALSE, '单号不能为空');
        }

        $NfPay = D('NfPay');
        $data = ['status' => \Common\Model\NfPayModel::STATUS_COMPLETE];
        if ($NfPay->where('pay_no= "'. $no .'"')->save($data)) {
            return result(TRUE);
        } else {
            return result(FALSE, '网络繁忙~');
        }
    }


}