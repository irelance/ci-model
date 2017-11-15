<?php
/**
 * Created by PhpStorm.
 * User: irelance
 * Date: 17-11-9
 * Time: 下午3:11
 */

namespace Irelance\Ci3\Model;

use CI_Model;
use JsonSerializable;

class SimpleObjectRelationalMappingModel extends CI_Model implements JsonSerializable
{
    const ALLOW_STATIC_CALL = ['find' => true, 'findFirst' => true, 'count' => true, 'pagination' => true];
    protected $_isNew = true;
    protected $_connection = 'default';
    protected $_table = '';
    protected $_pk = 'id';
    protected $_fields = [];
    protected $_outputFields = null;
    protected $_relationFields = [];
    protected $_distributeId = false;
    protected $_timeLogs = true;

    protected $_pkValue = null;

    public $needRelationFields = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->database($this->_connection);
        foreach ($this->_fields as $field) {
            $this->$field = null;
        }
        if (is_null($this->_outputFields)) {
            $this->_outputFields = $this->_fields;
        }
    }

    public function refresh()
    {
        if (!$this->_isNew && $this->_pkValue) {
            $query = $this->db->query('SELECT * FROM ' . $this->_table . ' WHERE ' . $this->_pk . '=?', [$this->_pkValue]);
            $data = $query->result_array();
            if (isset($data[0])) {
                $this->setData($data);
            }
        }
        return $this;
    }

    public function getPrimaryKeyValue()
    {
        return $this->_pkValue;
    }

    public function setData($data, $value = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($this->_pk == $key) {
                    if (!$this->_isNew || !$this->_distributeId) {
                        continue;
                    }
                    if ($this->_distributeId) {
                        $this->_pkValue = $value;
                    }
                }
                $this->$key = $value;
            }
        } elseif ($this->_pk != $data) {
            $this->$data = $value;
        }
        return $this;
    }

    public function save()
    {
        $data = [];
        if ($this->_timeLogs) {
            if ($this->_isNew) {
                $this->created_at = $this->timer();
            }
            $this->updated_at = $this->timer();
        }
        foreach ($this->_fields as $field) {
            if (!$this->_isNew && $this->_pk == $field) {
                $this->$field = $this->_pkValue;
            }
            $data[$field] = $this->$field;
        }
        if ($this->_isNew) {
            if ($result = $this->db->insert($this->_table, $data)) {
                $this->_isNew = false;
                if (!$this->_distributeId) {
                    $this->{$this->_pk} = $this->db->insert_id();
                    $this->_pkValue = $this->{$this->_pk};
                }
            }
            return $result;
        } else {
            return $this->db->update($this->_table, $data, $this->_pk . '=' . $this->_pkValue);
        }
    }

    public function delete()
    {
        if ($this->_isNew) {
            return true;
        }
        if ($result = $this->db->delete($this->_table, array($this->_pk => $this->{$this->_pk}))) {
            $this->_pkValue = $this->{$this->_pk} = null;
            $this->_isNew = true;
        }
        return $result;
    }

    public function toArray()
    {
        $data = [];
        foreach ($this->_outputFields as $field) {
            $data[$field] = $this->$field;
        }
        if ($this->needRelationFields) {
            foreach ($this->_relationFields as $field => $bool) {
                $data[$field] = $this->$field;
            }
        }
        return $data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    protected function _find($params = [])
    {
        $build = $this->buildQuery($params);
        $query = $this->db->query($build['sql'], $build['bind']);
        $data = $query->result_array();
        $result = new ModelSet();
        if (isset($data[0])) {
            foreach ($data as $item) {
                $one = new static();
                $one->_isNew = false;
                foreach ($item as $key => $value) {
                    if ($one->_pk == $key) {
                        $one->_pkValue = $value;
                    }
                    $one->$key = $value;
                }
                $result->push($one);
            }
        }
        return $result;
    }

    protected function _findFirst($params = [])
    {
        if (!is_array($params)) {
            $params = ['conditions' => $this->_pk . '=?', 'bind' => [$params]];
        }
        $params['limit'] = 1;
        $models = $this->_find($params);
        return isset($models[0]) ? $models[0] : null;
    }

    protected function _count($params = [])
    {
        unset($params['limit']);
        unset($params['order']);
        $params['columns'] = 'count(*) as total';
        $build = $this->buildQuery($params);
        $query = $this->db->query($build['sql'], $build['bind']);
        $data = $query->result_array();
        return $data[0]['total'];
    }

    protected function _pagination($pagesize, $params = [], $config = [])
    {
        unset($params['limit']);
        $paginator = new Paginator();
        $paginator->initialize($this, $params, array_merge($config, [
            'per_page' => $pagesize,
        ]));
        return $paginator;
    }

    protected function buildQuery($params)
    {
        $params = $this->buildParams($params);
        $sql = 'SELECT ' . $params['columns'] . ' FROM ' . $this->_table;
        //conditions
        if ($params['conditions']) {
            $sql .= ' WHERE ' . $params['conditions'];
        }
        //order
        if ($params['order']) {
            $sql .= ' ORDER BY ' . $params['order'];
        }
        //limit
        if ($params['limit']) {
            $sql .= ' LIMIT ' . $params['limit'];
        }
        //bind
        return ['sql' => $sql, 'bind' => $params['bind']];
    }

    protected function buildParams($params)
    {
        isset($params['conditions']) ?: $params['conditions'] = '';
        is_string($params['conditions']) ?: $params['conditions'] = '';
        isset($params['bind']) ?: $params['bind'] = [];
        is_array($params['bind']) ?: $params['bind'] = [];
        if (is_array($params['conditions'])) {
            $params['bind'] = [];
            $conditions = [];
            foreach ($params['conditions'] as $key => $value) {
                $v = '';
                if (is_string($value)) {
                    $v = $key . '=?';
                } elseif (is_array($value) && 2 == count($value)) {
                    $v = $key . $value[0] . '?';
                } else {
                    continue;
                }
                $params['bind'][] = $value[1];
                $conditions[] = $v;
            }
            $params['conditions'] = implode('and', $conditions);
        }
        isset($params['order']) ?: $params['order'] = '';
        isset($params['limit']) ?: $params['limit'] = '';
        if (is_array($params['limit']) && 2 == count($params['limit'])) {
            $params['limit'] = $params['limit'][0] . ',' . $params['limit'][1];
        }
        isset($params['columns']) ?: $params['columns'] = '*';
        if (is_array($params['columns'])) {
            $params['columns'] = implode(',', $params['columns']);
        }
        return $params;
    }

    protected function hasOne($key, $modelPath, $relateKey, $extra = [])
    {
        list($functionName, $name) = $this->buildRelationName($modelPath, $extra);
        $params = isset($extra['params']) ? $extra['params'] : [];
        $this->$functionName = function ($force = false) use ($key, $modelPath, $relateKey, $name, $params) {
            if (isset($this->$name) && !$force) {
                return $this->$name;
            }
            $this->load->model($modelPath);
            $modelName = basename($modelPath);
            $this->$name = $this->$modelName->findFirst(array_merge($params, [
                'conditions' => $relateKey . '=?', 'bind' => [$this->$key],
            ]));
            $this->_relationFields[$name] = true;
            return $this->$name;
        };
    }

    protected function hasMany($key, $modelPath, $relateKey, $extra = [])
    {
        list($functionName, $name) = $this->buildRelationName($modelPath, $extra);
        $params = isset($extra['params']) ? $extra['params'] : [];
        $this->$functionName = function ($force = false) use ($key, $modelPath, $relateKey, $name, $params) {
            if (isset($this->$name) && !$force) {
                return $this->$name;
            }
            $this->load->model($modelPath);
            $modelName = basename($modelPath);
            $this->$name = $this->$modelName->find(array_merge($params, [
                'conditions' => $relateKey . '=?', 'bind' => [$this->$key],
            ]));
            $this->_relationFields[$name] = true;
            return $this->$name;
        };
    }

    protected function hasManyToMany($key, $linkModelPath, $linkInKey, $linkOutKey, $modelPath, $relateKey, $extra = [])
    {
        list($functionName, $name) = $this->buildRelationName($modelPath, $extra);
        $this->$functionName = function ($force = false) use ($key, $linkModelPath, $linkInKey, $linkOutKey, $modelPath, $relateKey, $name) {
            if (isset($this->$name) && !$force) {
                return $this->$name;
            }
            $this->load->model($linkModelPath);
            $linkModelName = basename($linkModelPath);
            $this->load->model($modelPath);
            $modelName = basename($modelPath);
            $relations = $this->$linkModelName->find([
                'conditions' => $linkInKey . '=?', 'bind' => [$this->$key],
            ]);
            $temp = [];
            foreach ($relations as $relation) {
                $model = $this->$modelName->findFirst([
                    'conditions' => $relateKey . '=?', 'bind' => [$relation->$linkOutKey],
                ]);
                if ($model) {
                    $temp[$model->getPrimaryKeyValue()] = $model;
                }
            }
            $result = new ModelSet();
            foreach ($temp as $model) {
                $result->push($model);
            }
            $this->$name = $result;
            $this->_relationFields[$name] = true;
            return $this->$name;
        };
    }

    protected function buildRelationName($modelPath, $extra)
    {
        if (isset($extra['alias']) && $extra['alias']) {
            $name = $extra['alias'];
        } else {
            $name = basename($modelPath);
        }
        return [
            'get' . ucfirst($name),
            strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name))
        ];
    }

    protected function timer()
    {
        return time();
    }

    public function __call($name, $arguments)
    {
        if (isset(static::ALLOW_STATIC_CALL[$name]) && static::ALLOW_STATIC_CALL[$name]) {
            $name = '_' . $name;
            return call_user_func_array([$this, $name], $arguments);
        }
        if (isset($this->$name) && is_callable($this->$name)) {
            $func = $this->$name;
            return $func($arguments);
        }
        throw new \Exception("Call to undefined method " . static::class . "::" . $name);
    }

    public static function __callStatic($name, $arguments)
    {
        $CI =& get_instance();
        $model = basename(static::class);
        if (!($instance = $CI->$model) ||
            !isset(static::ALLOW_STATIC_CALL[$name]) ||
            !static::ALLOW_STATIC_CALL[$name]) {
            throw new \Exception("Call to undefined static method " . static::class . "::" . $name);
        }
        $name = '_' . $name;
        return call_user_func_array([$instance, $name], $arguments);
    }

    public function getOutputFields()
    {
        return $this->_output;
    }

    public function setOutputFields($fields)
    {
        $this->_output = $fields;
        return $this;
    }
}