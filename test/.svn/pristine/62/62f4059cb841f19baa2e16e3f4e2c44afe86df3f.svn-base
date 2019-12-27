<?php

namespace app\api\controller;

class Wxapp extends BaseController{

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($sessionKey, $encryptedData, $iv, &$data){
        if (strlen($sessionKey) != 24) {
            return ['code' => -91001];
        }

        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            return ['code' => -91002];
        }

        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = $this->decrypt($aesKey, $aesCipher, $aesIV);

        if ($result['code'] != 0) {
            return ['code' => $result['code']];
        }

        $dataObj = json_decode($result['data']);

        if ($dataObj == NULL) {
            return ['code' => -91003];
        }

        $data = $result['data'];
        return ['code' => 0];
    }

    /**
     * 对密文进行解密
     * @param string $aesCipher 需要解密的密文
     * @param string $aesIV 解密的初始向量
     * @return string 解密得到的明文
     */
    public function decrypt($aesKey, $aesCipher, $aesIV = '' ){

        try {
            if (empty($aesIV)) {
                $mcrypt_mode = MCRYPT_MODE_ECB;
            } else {
                $mcrypt_mode = MCRYPT_MODE_CBC;
            }
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', $mcrypt_mode, '');

            @mcrypt_generic_init($module, $aesKey, $aesIV);

            //解密
            $decrypted = mdecrypt_generic($module, $aesCipher);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        } catch (\Exception $e) {
            return ['code' => -41003];
        }

        try {

            //去除补位字符
            $result = $this->decode($decrypted);

        } catch (\Exception $e) {
            return ['code'=>-41004];
        }
        return ['code'=>0, 'data'=>$result];
    }

    /**
     * 对解密后的明文进行补位删除
     * @param decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    public function decode($text){
        $pad = ord(substr($text, -1));
        if ($pad < 1 || 32 < $pad) {
            $pad = 0;
        }

        return substr($text, 0, strlen($text) - $pad);
    }
}