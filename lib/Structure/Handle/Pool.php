<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Pool extends Filter {
    
    protected $_defaultOptions = [
        'filters' => [],
    ];

    public function filter($var) {
        foreach ($this->_options['filters'] as $filter) {
            $filter = self::factory($filter);
            if ($filter->validate($var)) {
                return $filter->filter($var);
            }
        }
        return null;
    }

    public function validate($var) {
        foreach ($this->_options['filters'] as $filter) {
            $filter = self::factory($filter);
            if ($filter->validate($var)) {
                return true;
            }
        }
        return false;
    }

}