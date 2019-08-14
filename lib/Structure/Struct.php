<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure;

use Structure\Helper\Keys;

class Struct extends Core {


    /**
     * key输出
     * @param bool $filterNull
     * @param string $scene
     * @return array
     */
    public function outputArrayByKey($filterNull = false,$scene = ''){
        $fields = $this->getFields();
        $_data = [];
        if($fields !== false){
            foreach ($fields as $f) {
                $f = $f->getName();
                if (!$this->_isKeyField($f,$scene)) {
                    continue; # 排除非key字段
                }

                if(!is_array($this->$f)){
                    if ($filterNull){
                        if (is_null($this->$f)) {
                            continue; # 过滤null字段
                        }
                    }
                }
                $res = $this->_parsingOperator($f,$this->$f);
                $_data[$res[0]] = $res[1];
            }
        }
        $this->settingReset();
        return $_data;
    }

    /**
     * 输出
     * @param int $filter
     * @param int $output
     * @param string $scene
     * @return array
     */
    public function outputArray($filter = Keys::FILTER_NORMAL,$output = Keys::OUTPUT_NORMAL,$scene = ''){
        $fields = $this->getFields();
        $_data = [];
        if($fields !== false){
            foreach ($fields as $f) {
                $f = $f->getName();

                if ($this->_isGhostField($f)) {
                    continue; # 排除鬼魂字段
                }

                if (!is_array($this->$f)){
                    switch ($filter){
                        case Keys::FILTER_KEY:
                            if (
                            !$this->_isKeyField($this->$f,$scene)
                            ) {
                                continue 2;
                            }
                            break;
                        case Keys::FILTER_STRICT:
                            if (
                                is_null($this->$f) or
                                $this->$f === '' or
                                $this->_isSkipField($f)
                            ) {
                                continue 2;
                            }
                            break;
                        case Keys::FILTER_NULL:
                            if (
                                is_null($this->$f) or
                                $this->_isSkipField($f)
                            ) {
                                continue 2;
                            }
                            break;
                        case Keys::FILTER_EMPTY:
                            if (
                                $this->$f === '' or
                                $this->_isSkipField($f)
                            ) {
                                continue 2;
                            }
                            break;
                        case Keys::FILTER_NORMAL:
                        default:
                            break;
                    }
                }

                switch ($output){
                    case Keys::OUTPUT_NULL:
                        $value = $this->$f === '' ? null : $this->$f;
                        break;
                    case Keys::OUTPUT_EMPTY:
                        $value = is_null($this->$f) ? '' : $this->$f;
                        break;
                    case Keys::OUTPUT_NORMAL:
                    default:
                        $value = $this->$f;
                        break;
                }

                $res = $this->_parsingOperator($f,$value);
                $_data[$res[0]] = $res[1];
            }
        }
        $this->settingReset();
        return $_data;
    }

    /**
     * 添加
     * @param array $data
     * @param bool $validate
     * @return bool
     */
    public function create(array $data, $validate = true) {
        $fields = $this->getFields();
        $_data = [];
        if($fields !== false){
            foreach ($fields as $f) {
                $f = $f->getName();
                if($this->getEmptyOrNull()){
                    $_data[$f] = (isset($data[$f]) and $data[$f] !== '') ? $data[$f] : $this->$f;
                }else{
                    $_data[$f] = isset($data[$f]) ? $data[$f] : $this->$f;
                }
            }

            # 赋值
            foreach ($_data as $f => $d) {
                $this->$f = $d;
            }

            # 验证
            if ($validate) {
                if (!$this->validate($_data)) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * 验证器
     * @param array|null $data
     * @return bool
     */
    public function validate(array $data = null) {
        # 初始化错误记录
        $this->_errors = [];

        if (!$this->_validate) {
            return true;
        }

        if (is_null($data)) {
            $data = $this->outputArray();
        }

        $passed = true;

        foreach ($this->_validate as $f => $v) {
            if ($this->_isSkipField($f)) {
                continue; # 排除skip字段
            }

            # ghost字段需要验证

            # 必填值验证
            if (isset($v['required'])) {
                foreach ($v['required'] as $req) {
                    if ($this->_validateScene($req['scene'])) {
                        if (
                            !isset($data[$f]) or
                            $data[$f] === ''
                        ) {
                            $this->_addError($f, $req['error']);
                            $passed = false;
                            continue 2; # 无值不验证
                        }
                    }
                }
            }
            # 规则验证
            if (isset($data[$f]) && $data[$f] !== '' && isset($v['rule'])) {
                foreach ($v['rule'] as $r) {
                    if ($this->_validateScene($r['scene'])) {
                        $validator = $r['content'];

                        # 创建错误(校验过程)
                        $check = true;
                        switch (true){
                            case $this->_rck == 'func':
                                $check = call_user_func($validator, $data[$f]);
                                break;
                            case $this->_rck == 'method':
                                $check = call_user_func($validator, $data[$f], $f, $data);
                                break;
                            case $validator instanceof Filter:
                                $check = $validator->validate($data[$f]);
                                break;
                        }
                        if(!$check){
                            $this->_addError($f, $r['error']);
                            $passed = false;
                        }
                    }
                }
            }
        }

        return $passed;
    }

    /**
     * 清空
     * @return bool
     */
    public function clean(){
        $fields = $this->getFields();
        if($fields !== false){
            foreach ($fields as $f) {
                $f = $f->getName();
                $this->$f = null;
            }
        }
        return true;
    }

    /**
     * 确认错误
     * @param null $filed
     * @return bool|mixed
     */
    public function hasError($filed = null) {
        if (is_null($filed)) {
            return count($this->_errors) > 0;
        } else {
            return $this->_errors[$filed];
        }
    }

    /**
     * 获取第一条错误
     * @return string|null
     */
    public function getError() {
        return $this->_errors ? array_values($this->_errors)[0] : null;
    }

    /**
     * 获取第一条错误码
     * @return string|null
     */
    public function getCode() {
        return $this->_codes ? array_values($this->_codes)[0] : null;
    }

    /**
     * 获取全部错误
     * @return array
     */
    public function getErrors() {
        return $this->_errors ? $this->_errors : [];
    }

    /**
     * 获取全部错误码
     * @return array
     */
    public function getCodes() {
        return $this->_codes ? $this->_codes : [];
    }

}
