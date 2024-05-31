<?php

declare (strict_types=1);

namespace app\controller;

use think\facade\Log;

/**
 * RSA加密对明文的长度有所限制，规定需加密的明文最大长度=密钥长度-11（单位是字节，即byte），所以在加密和解密的过程中需要分块进行。
 * 而密钥默认是1024位，即1024位/8位-11=128-11=117字节。所以默认加密前的明文最大长度117字节，解密密文最大长度为128字。
 * 那么为啥两者相差11字节呢？是因为RSA加密使用到了填充模式（padding），即内容不足117字节时会自动填满，用到填充模式自然会占用一定的字节，而且这部分字节也是参与加密的
 */
class RSA
{
    private object $rsa;

    public function __construct()
    {
        $this->rsa = new \app\service\RSA(runtime_path() . 'certs');
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function test()
    {
        $resp = [];
        $config = array(
//            'config' => 'D:\download\php-8.2.12-nts-Win32-vs16-x64\extras\ssl\openssl.cnf', // window运行需要加这一行
            "private_key_bits" => 4096,           //字节数  512 1024 2048  4096 等 ,不能加引号，此处长度与加密的字符串长度有关系，可以自己测试一下
            "private_key_type" => OPENSSL_KEYTYPE_RSA,   //加密类型
        );
        $res = openssl_pkey_new($config);
        //提取私钥
        openssl_pkey_export($res, $private_key, null, $config);
        //生成公钥
        $public_key = openssl_pkey_get_details($res)["key"];
        $resp['私钥'] = $private_key;
        $resp['公钥'] = $public_key;

        //要加密的数据
        $data = "http://www.cnblogs.com/wt645631686/";
        $resp['加密的数据：'] = $data;
        //私钥加密后的数据
        openssl_private_encrypt($data, $encrypted, $private_key);
        //加密后的内容通常含有特殊字符，需要base64编码转换下
        $encrypted = base64_encode($encrypted);
        $resp['私钥加密后的数据：'] = $encrypted;
        //公钥解密
        openssl_public_decrypt(base64_decode($encrypted), $decrypted, $public_key);
        $resp['公钥解密后的数据：'] = $decrypted;

        //----相反操作。公钥加密
        openssl_public_encrypt($data, $encrypted, $public_key);
        $encrypted = base64_encode($encrypted);
        $resp['公钥加密后的数据：'] = $encrypted;

        openssl_private_decrypt(base64_decode($encrypted), $decrypted, $private_key);//私钥解密
        $resp['私钥解密后的数据：'] = $decrypted;
        return json($resp);
    }

    /**
     * 生成证书文件
     * Enter PEM pass phrase：window 可能在这卡住
     * @return \think\Response
     */
    public function test2()
    {
        ini_set('max_execution_time', 60 * 60);
        $this->rsa->generate();
        $this->rsa->cert();
        return json('success');
    }

    /**
     * 基于pkcs1/8标准的加解密
     * 加密测试
     * 公钥加密、私钥解密、私钥签名、公钥验签
     * @return \think\Response
     */
    public function test3()
    {
        $data = 'test';
        $public_key = file_get_contents(runtime_path() . 'certs' . DIRECTORY_SEPARATOR . '_public.key');
        $private_key = file_get_contents(runtime_path() . 'certs' . DIRECTORY_SEPARATOR . '_private.key');
        openssl_public_encrypt($data, $encrypted, $public_key);
        Log::info('test 公钥加密后的数据(base64_encode)：' . base64_encode($encrypted));
        openssl_private_decrypt($encrypted, $decrypted, openssl_pkey_get_private($private_key, $this->rsa->getPassPhrase()));
        Log::info('用私钥解密：' . $decrypted);
        openssl_public_decrypt($encrypted, $decrypted2, $public_key);
        Log::info('用公钥解密：' . $decrypted2);
        openssl_sign($encrypted, $sign, openssl_pkey_get_private($private_key, $this->rsa->getPassPhrase()));
        Log::info('用私钥签名：' . base64_encode($sign));
        $verify = openssl_verify($encrypted, $sign, $public_key);
        Log::info('验签结果：' . $verify);//成功1，失败0
    }

    /**
     * 基于pkcs12标准的加解密
     * 加密测试
     * 公钥加密、私钥解密、私钥签名、公钥验签
     * @return \think\Response
     */
    public function test4()
    {
        $data = 'test';
        $public_key = file_get_contents(runtime_path() . 'certs' . DIRECTORY_SEPARATOR . '_cert.cer');
        $public_key = openssl_pkey_get_public($public_key);
        $private_key = file_get_contents(runtime_path() . 'certs' . DIRECTORY_SEPARATOR . '_private.pfx');
        $priKeys = [];
        openssl_pkcs12_read($private_key, $priKeys, $this->rsa->getPassPhrase());
        $priKey = openssl_pkey_get_private($priKeys['pkey']);
        openssl_public_encrypt($data, $encrypted, $public_key);
        Log::info('test 公钥加密后的数据(base64_encode)：' . base64_encode($encrypted));
        openssl_private_decrypt($encrypted, $decrypted, $priKey);
        Log::info('用私钥解密：' . $decrypted);
        openssl_public_decrypt($encrypted, $decrypted2, $public_key);
        Log::info('用公钥解密：' . $decrypted2);
        openssl_sign($encrypted, $sign, $priKey);
        Log::info('用私钥签名：' . base64_encode($sign));
        $verify = openssl_verify($encrypted, $sign, $public_key);
        Log::info('验签结果：' . $verify);
    }

    /**
     * aes加密测试
     *
     * @return \think\Response
     */
    public function test5()
    {
        $str = 'test';
        $pass = '12345';
        $iv = openssl_random_pseudo_bytes(16, $crypto_strong);
        Log::info('$iv：' . $iv);
        if ($iv === false || $crypto_strong === false) {
            return;
        }
        $encrypt = base64_encode(openssl_encrypt($str, 'AES-256-CBC', $pass, OPENSSL_RAW_DATA, $iv));
        Log::info('加密结果：' . $encrypt);

        $decrypt = openssl_decrypt(base64_decode($encrypt), 'AES-256-CBC', $pass, OPENSSL_RAW_DATA, $iv);
        Log::info('解密结果：' . $decrypt);
    }
}
