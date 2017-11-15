<?php
/**
 * Created by PhpStorm.
 * User: irelance
 * Date: 17-11-10
 * Time: 下午7:28
 */

namespace Irelance\Ci3\Model;


class Paginator extends Set
{
    protected $_CI;
    protected $perPage;
    protected $totalPage;
    protected $currentPage;
    protected $pageName = 'page';

    public function __construct()
    {
        $this->_CI =& get_instance();
        $this->_CI->load->library('pagination');
    }

    public function initialize($model, $params, $config = [])
    {
        /* @var SimpleObjectRelationalMappingModel $model */
        $this->perPage = $config['per_page'];
        if (!is_numeric($this->perPage) || $this->perPage < 1) {
            throw new \Exception('Invalid value per_page');
        }
        if (isset($config['query_string_segment']) && !$config['query_string_segment']) {
            $this->pageName = $config['query_string_segment'];
        } else {
            $config['query_string_segment'] = $this->pageName;
        }
        $config['use_page_numbers'] = true;
        $config['page_query_string'] = true;
        $config['base_url'] = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['PATH_INFO'];
        $config['total_rows'] = $model->count($params);
        $this->_CI->pagination->initialize($config);
        //end native pagination initialize
        $this->currentPage = $this->_CI->input->get($this->pageName) ?: 1;
        $this->totalPage = ceil($config['total_rows'] / $this->perPage);
        if ($this->currentPage < 1 || $this->currentPage > $this->totalPage) {
            $this->_data = new ModelSet();
            return false;
        }
        $offset = ($this->currentPage - 1) * $this->perPage;
        $params['limit'] = [$offset, $this->perPage,];
        $this->_data = $model->find($params);
        return true;
    }

    public function links()
    {
        return $this->_CI->pagination->create_links();
    }

    public function toArray()
    {
        return [
            'total_page' => $this->totalPage,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'items' => $this->_data->toArray(),
        ];
    }

    public function setOutputFields($fields)
    {
        $this->_data->setOutputFields($fields);
        return $this;
    }
}