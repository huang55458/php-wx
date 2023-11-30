<?php
declare (strict_types = 1);

namespace app\service;

use think\facade\Log;

class RSA extends \think\Service
{
    /**
     * 过期时间
     * @var int|mixed
     */
    protected $expire;

    /**
     * 密钥密码
     * @var string
     */
    protected $pass_phrase;

    /**
     * 密钥保存路径
     * @var string
     */
    protected $path;

    /**
     * 私钥
     * @var string
     */
    protected $private_key;

    /**
     * 时间戳
     * @var integer
     */
    protected $time;

    /**
     * 基础配置
     * @var string[]
     */
    public array $dn = [
        "countryName" => "CN", "stateOrProvinceName" => "ChongQing", "localityName" => "China",
        "organizationName" => "The Brain Room Limited", "organizationalUnitName" => "PHP Documentation Team",
        "commonName" => "Rancy Bruce", "emailAddress" => "rancy@rancy.top"
    ];

    /**
     * 密钥配置
     * @var array
     */
    public array $config = [
        // window运行需要加这一行
//        'config'=> 'D:\download\php-8.2.12-nts-Win32-vs16-x64\extras\ssl\openssl.cnf',
        //指定应该使用多少位来生成私钥  512 1024  2048  4096等
        "private_key_bits" => 1024,
        //选择在创建CSR时应该使用哪些扩展。可选值有 OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_RSA 或 OPENSSL_KEYTYPE_EC. 默认值是 OPENSSL_KEYTYPE_RSA.
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    /**
     * OpensslServer constructor.
     * @param string $path 保存路径
     * @param int $expire 有效期天数
     * @param string $pass_phrase 密钥密码
     */
    public function __construct($path = null, $expire = 365, $pass_phrase = 'rancy')
    {
        $this->path = $path;
        $this->expire = $expire;
        $this->pass_phrase = $pass_phrase;
        $this->time = '';
    }

    /**
     * 生成密钥对
     */
    public function generate()
    {
        // 生成公钥私钥资源
        $res = openssl_pkey_new($this->config);
        // 导出私钥 $this->private_key
        openssl_pkey_export($res, $this->private_key, $this->pass_phrase, $this->config);
        //  导出公钥 $pubKey
        $pubKey = openssl_pkey_get_details($res);

        //var_dump($this->private_key);
        //var_dump($pubKey);
        file_put_contents(($this->path) .DIRECTORY_SEPARATOR."{$this->time}_private.key", $this->private_key);
        file_put_contents(($this->path) .DIRECTORY_SEPARATOR."{$this->time}_public.key", $pubKey["key"]);
    }

    /**
     * 签名证书
     */
    public function cert()
    {
        //基于$dn生成新的 CSR （证书签名请求）
        $csr = openssl_csr_new($this->dn, $this->private_key, $this->config);
        //根据配置自己对证书进行签名
        $csr_sign = openssl_csr_sign($csr, null, $this->private_key, $this->expire, $this->config);
        //将公钥证书存储到一个变量 $csr_key，由 PEM 编码格式命名。
        openssl_x509_export($csr_sign, $csr_key);
        //将私钥存储到名为的出 PKCS12 文件格式的字符串。
        openssl_pkcs12_export($csr_sign, $private_pkcs12, $this->private_key, $this->pass_phrase);

        //var_dump($csr_key);
        //var_dump($private_pkcs12);

        file_put_contents(($this->path) .DIRECTORY_SEPARATOR."{$this->time}_cert.cer", $csr_key);
        file_put_contents(($this->path) .DIRECTORY_SEPARATOR."{$this->time}_private.pfx", $private_pkcs12);
    }

    /**
     * @return string
     */
    public function getPassPhrase(): string
    {
        return $this->pass_phrase;
    }
}
