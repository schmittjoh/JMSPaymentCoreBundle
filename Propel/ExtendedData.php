<?php

namespace JMS\Payment\CoreBundle\Propel;

use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Propel\om\BaseExtendedData;

/**
 * Extended data entity
 */
class ExtendedData extends BaseExtendedData implements ExtendedDataInterface
{
    /**
     * @param string $name
     *
     * @return boolean
     */
    public function isEncryptionRequired($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }

        $datas = $this->all();

        return $datas[$name][1];
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function mayBePersisted($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }

        $datas = $this->all();

        return $datas[$name][2];
    }

    /**
     * @param string $name
     */
    public function remove($name)
    {
        $datas = $this->all();
        unset($datas[$name]);

        $this->setDatas(array());
        foreach ($datas as $key => $data) {
            $this->addData(json_encode(array($key => $data), true));
        }
    }

    /**
     * @param string  $name
     * @param mixed   $value
     * @param boolean $encrypt
     * @param boolean $persist
     */
    public function set($name, $value, $encrypt = true, $persist = true)
    {
        if ($encrypt && !$persist) {
            throw new \InvalidArgumentException(sprintf('Non persisted field cannot be encrypted "%s".', $name));
        }

        $this->remove($name);
        $this->addData(json_encode(array($name => array($value, $encrypt, $persist)), true));
    }

    /**
     * @return mixed
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }

        $datas = $this->all();

        return $datas[$name][0];
    }

    /**
     * @return boolean
     */
    public function has($name)
    {
        $datas = $this->getDatas();

        foreach ($datas as $data) {
            $decoded = json_decode($data, true);
            if (isset($decoded[$name])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function all()
    {
        $datas = $this->getDatas();
        $return = array();

        foreach ($datas as $data) {
            $decoded = json_decode($data, true);
            $return[key($decoded)] = current($decoded);
        }

        return $return;
    }

    /**
     * @return boolean
     */
    public function equals($data)
    {
        if (!$data instanceof ExtendedDataInterface) {
            return false;
        }

        return $data->getData() === $this->getData();
    }
}
