<?php

namespace JMS\Payment\CoreBundle\Cryptography;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This implementation transmits the initialization vector (IV) alongside
 * the encrypted data. Choose your cipher/mode combination with care as
 * this might severely compromise the strength of the applied algorithm.
 *
 * Defaults should be fine for almost all cases.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MCryptEncryptionService implements EncryptionServiceInterface
{
    protected $cipher;
    protected $key;
    protected $mode;

    /**
     * Constructor.
     *
     * @param string $secret
     * @param string $cipher
     * @param string $mode
     */
    public function __construct($secret, $cipher = 'rijndael-256', $mode = 'ctr')
    {
        if (!extension_loaded('mcrypt')) {
            throw new \RuntimeException('The mcrypt extension must be loaded.');
        }

        @trigger_error('mcrypt has been deprecated in PHP 7.1 and is removed in PHP 7.2. Refer to http://jmspaymentcorebundle.readthedocs.io/en/stable/guides/mcrypt.html for instructions on how to migrate away from mcrypt', E_USER_DEPRECATED);

        if (!in_array($cipher, @mcrypt_list_algorithms(), true)) {
            throw new \InvalidArgumentException(sprintf('The cipher "%s" is not supported.', $cipher));
        }

        if (!in_array($mode, @mcrypt_list_modes(), true)) {
            throw new \InvalidArgumentException(sprintf('The mode "%s" is not supported.', $mode));
        }

        $this->cipher = $cipher;
        $this->mode = $mode;

        if (0 === strlen($secret)) {
            throw new \InvalidArgumentException('$secret must not be empty.');
        }

        $key = hash('sha256', $secret, true);
        if (strlen($key) > $size = @mcrypt_get_key_size($this->cipher, $this->mode)) {
            $key = substr($key, 0, $size);
        }
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($encryptedValue)
    {
        $size = mcrypt_get_iv_size($this->cipher, $this->mode);
        $encryptedValue = base64_decode($encryptedValue);
        $iv = substr($encryptedValue, 0, $size);

        return rtrim(mcrypt_decrypt($this->cipher, $this->key, substr($encryptedValue, $size), $this->mode, $iv));
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($rawValue)
    {
        $size = mcrypt_get_iv_size($this->cipher, $this->mode);
        $iv = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

        return base64_encode($iv.mcrypt_encrypt($this->cipher, $this->key, $rawValue, $this->mode, $iv));
    }

    public function getCipher()
    {
        return $this->cipher;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getMode()
    {
        return $this->mode;
    }
}
