<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure\Handle;

use Structure\Filter;

class Object extends Filter {
    
    protected $defaultOptions = [
        'class' => '',
    ];

    public function filter($var) {
        if (!is_object($var)) {
            return null;
        }
        if (self::$options['class'] && !$var instanceof self::$options['class']) {
            return null;
        }
        return $var;
    }

    public function validate($var) {
        return $var === $this->filter($var);
    }

}