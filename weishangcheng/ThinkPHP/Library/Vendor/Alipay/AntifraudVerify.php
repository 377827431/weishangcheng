<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/9/18
 * Time: 16:13
 */

namespace Alipay;
require_once 'aop/request/ZhimaCreditAntifraudVerifyRequest.php';
require_once 'aop/AopClient.php';

class AntifraudVerify
{
    public static function verify($param){
        ini_set("display_errors", "On");
        error_reporting(E_ALL | E_STRICT);
        $c = new \AopClient();
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
//$c->gatewayUrl = "https://openapi.alipaydev.com/gateway.do"; //沙箱
        $c->appId = "2017091808801645";
//$c->appId = "2016081900287121";  //沙箱
        $c->rsaPrivateKey = 'MIIEogIBAAKCAQEA+NEIaVLHuk0bbbv2y08lJ+VrxLu6eAXOlnzhj0/3Jesh2IeaWXf9q1CkxRan9c+xGr1WExiRrYROmlvVoL0JsmqRzyXY57gqi9zVSqHmZKHSmmPEvlUtsl2xs/I5VUXcigXWk2qHTkuc0/721oedgd2FbFDpbFKpKUUZLaNZ+3/7V4a0Df1M6GdRm6wVmdW7aca+6RqOb2RP54ndsclveblQ5FdnW/msUCQvBWEsBCE2MGxXw3Lg8vRjhASKzfHAiOjgGjdwgckMxhqKMdt0zEEPsUw0zRQtqmzzUat407jV6M+CWWCOZXSXKqIFF+PboHmqGpJI4mD7u8bGruM8TwIDAQABAoIBABHUKJ4jaFxZGhYK2exHh4oMTmSSbxIamGsAF8mFGViGOK6jSNQQXQThimz06qQadb5MwtYrdITSbi9xVSVnZkJ6kGgfdiNkduf+suneH/wl/ElDzN02jUeynwEd4i3SC7N4J5/4iil6EYq/QkCtBwQ/M0hHo/I3Ghfy85LpmZEDvntRtwk9lXjndn1Lb20nUJyvJ5+1ZKCMqAX3+ggj1G6moTKSBti3V39uRVMcSB55qmioACwCWrgpRCbaFy7M4XcFSBo21f54obytMsN1TPj2VDW7kS+jSfDOKJLFFSVdYt5P4M9h6XbD3OAd2t1M71Lr/zgHGbJF3oEMNEwG1gECgYEA/grw8fwqywi62ZrVJWP1vPxfcSBwcWS2eVH4cIkJmWV0i8cja/c12Vj/0mNLikQs6NTnrbNuoca8Th2v3nrYsdbQ2dytxtL0nsVIJPKaZ6CMzZ9EwIrua0AQ/yEGXtP1CNlBR4rk+Qj5L1YP/U3B7jQwyIXekMddoRpBh9beiOECgYEA+rvIp73WEQCMqlVXVcsYX+Y4Z5Oe+WRy7m1yBqpkcFQ180bk98FR/HacCNLXhv8YA/ak5EiS7Qu6rufz9mptNpsMTAvg3+h41YJoA4V3Gzibjbuwudy5hEOKG2PMW0h1SZaaDX2nJh1kF83hPbIoN5ILtVBSzrVr+r6XtQTEey8CgYBAKy9VUGbWxiu8T3nLagZmaDELeDAu1EurNWNVuaetEY1wySpPWTBG6E4mLGKmWDYn0a97lrk5L+Pcr27++XTG5wX2IeHbOOoFOLvSaV1LE6i9P5+0KuOyP4qLhyH+zfc09vugQJs5tGSM6mY7i2qS6qfv3rCrTVB/IwyubT3kwQKBgDFgTZGK9t7+RrU8fShuCGzKP41WKtZeC6wcbXoWkBT24HD6IxkPPwACs5OhQcRZ8/bD2ZEDIbwAtVDAaPC74KoCOpe3Nx+g/jq9pZIb9Gqt6SQuNA1GBFqhmk7uhk3rpP1K5SeG+SWuYAm4B4VI0lavMhMQsF34qD0Gz4VcXP0NAoGAQ6BMVamR+R17eQjFuXbSg2ydgdIsqX/2zj2pGMwmQ305f892QaNYTIbt0OlAd9S1CIipHsm2dJnAWQBUZFRJwGI1LucgFX6MURFgRMdbr6lmshK9OuA/7FDluPFYq/zWogeXBozH6rien4DvASCsqvpgXQYpYbxL7WuKAF5PPPo=' ;
        $c->format = "json";
        $c->charset= "GBK";
        $c->signType= "RSA2";
        $c->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwMMk7pDSmLU5fBmR/QrrVTqCSN7svB4bolprUIRd4pg/m1FvesFSkBUAx8BxpTWBy/quEe2sXF/awKkNGtCQrfO4rM9p7rkz+Dv0DwpbEniSzP43uEPMlcQZKAJdjJ7eEyYZEmCxeV7UR//WRvkklkHlTzM2oS0obF0pQX8PfyhOOjXGTvBkNLViBlv7HZ2G4oWgwDBUK8PnqwYbdhvtBjcAw4TZCMfKyky95MGtUV/I3w5iVIlyWBx0IjXAc9idAKNVffaUZhw8nvByZd476ylm7nWPybv8uxm9saWcLSf9pEzMQrCev8FZQV7COV2FQ23lCDD5lOtP+WC0XtjEtwIDAQAB';
        $request = new \ZhimaCreditAntifraudVerifyRequest();
        $re = "{
            'mobile': '".$param['mobile']."',
            'name': '".$param['name']."',
            'product_code': 'w1010100000000002859',
            'transaction_id': '".$param['transaction_id']."',
            'cert_type': 'IDENTITY_CARD',
            'cert_no': '".$param['cert_no']."',
        }";
        $request->setBizContent($re);
        $response= $c->execute($request);
        return $response;
    }
}