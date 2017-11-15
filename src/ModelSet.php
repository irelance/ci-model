<?php
/**
 * Created by PhpStorm.
 * User: irelance
 * Date: 17-11-10
 * Time: 上午10:18
 */

namespace Irelance\Ci3\Model;


class ModelSet extends Set
{
    public function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }

    public function push($model)
    {
        if ($model instanceof SimpleObjectRelationalMappingModel) {
            return array_push($this->_data, $model);
        }
        return false;
    }

    public function save()
    {
        $this->db->trans_start();
        /* @var SimpleObjectRelationalMappingModel $model */
        foreach ($this->_data as $model) {
            if (false === $model->save()) {
                $this->db->trans_rollback();
                return false;
            }
        }
        $this->db->trans_commit();
        return true;
    }

    public function delete()
    {
        $this->db->trans_start();
        /* @var SimpleObjectRelationalMappingModel $model */
        foreach ($this->_data as $model) {
            if (false === $model->delete()) {
                $this->db->trans_rollback();
                return false;
            }
        }
        $this->db->trans_commit();
        return true;
    }

    public function setData($data, $value = null)
    {
        /* @var SimpleObjectRelationalMappingModel $model */
        foreach ($this->_data as $model) {
            $model->setData($data, $value);
        }
        return $this;
    }

    public function toArray()
    {
        $result = [];
        /* @var SimpleObjectRelationalMappingModel $model */
        foreach ($this->_data as $model) {
            $result[] = $model->toArray();
        }
        return $result;
    }

    public function setOutputFields($fields)
    {
        /* @var SimpleObjectRelationalMappingModel $model */
        foreach ($this->_data as $model) {
            $result[] = $model->setOutputFields($fields);
        }
        return $this;
    }
}