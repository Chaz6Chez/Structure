<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Booleans extends Filter {
    protected $_filterName = 'bool 过滤器';
    protected $_defaultOptions = [
        'default' => null,
    ];

    public function filter($var) {
        return filter_var($var, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function validate($var) {
        return $this->filter($var) !== null;
    }
}