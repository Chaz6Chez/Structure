<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure;

class Struct {

    /**
     * 子类继承重写
     *
     * @var bool 是否将空字符串转换成null
     */
    protected $_empty_to_null = true;

    /**
     * @var array 验证信息
     */
    protected $_validate = [];

    /**
     * @var array 错误
     */
    protected $_errors = [];

    /**
     * @var array 错误码
     */
    protected $_codes = [];

    /**
     * @var string 当前场景
     */
    protected $_scene = '';

    /**
     * @var string Rule内容键
     * rule content key
     */
    protected $_rck = '';

    /**
     * @var string Rule内容
     * rule content string
     */
    protected $_rcs = '';

    /**
     * @var array Rule内容配置
     * rule content options
     */
    protected $_rco = [];

    /**
     * Struct constructor.
     * @param null $data
     * @param string $scene
     * @throws \ReflectionException
     */
    public function __construct($data = null, $scene = '') {
        $this->_scalpel();                       # 加载手术刀
        $this->setScene($scene);                 # 加载场景
        $this->_setDefault();                    # 加载默认值
        if (!is_null($data)) {
            $this->create($data, false); # 创建数据
        }
    }

    /**
     * 设置empty to null
     * @param bool $bool
     */
    public function emptyToNull(bool $bool){
        $this->_empty_to_null = $bool;
    }

    /**
     * 获得模型实例,此操作未作数据验证
     * @param null|array $data
     * @param string $scene 场景
     * @return static
     */
    public static function factory($data = null, $scene = '') {
        $cls = get_called_class();
        return new $cls($data, $scene);
    }

    /**
     * 设置场景
     * @param $scene
     * @return $this
     */
    public function setScene($scene) {
        $this->_scene = $scene;
        return $this;
    }

    /**
     * 返回数组格式的数据
     * @param bool $filterNull 过滤NULL的开关 默认不过滤
     * @return array
     */
    public function toArray($filterNull = false) {
        $fields = $this->_getFields();
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();

            if ($this->_isGhostField($f)) {
                continue; # 排除鬼魂字段
            }

            if ($filterNull && !is_array($this->$f)) {
                if ('null' == strtolower($this->$f)) {
                    continue; # 过滤null字段
                }
                if (is_null($this->$f)) {
                    continue; # 过滤null字段
                }

                if ($this->_isSkipField($f)) {
                    continue; # 排除skip字段
                }
            }
            $_data[$f] = $this->$f;
        }

        return $_data;
    }

    /**
     * 较严格的返回数组数据 (默认过滤空字符串)
     * @param bool $filterNull
     * @return array
     */
    public function toArrayStrict($filterNull = false){
        $fields = $this->_getFields();
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();

            if ($this->_isGhostField($f)) {
                continue; # 排除鬼魂字段
            }
            if(!is_array($this->$f)){
                if ($this->$f === '') {
                    continue; # 过滤空字符串
                }
                if ($filterNull){
                    if ('null' == strtolower($this->$f)) {
                        continue; # 过滤字符串null字段
                    }
                    if (is_null($this->$f)) {
                        continue; # 过滤null字段
                    }
                    if ($this->_isSkipField($f)) {
                        continue; # 排除skip字段
                    }
                }
            }
            
            $_data[$f] = $this->$f;
        }

        return $_data;
    }

    /**
     * 批量赋值字段
     * @param array $data
     * @param bool $validate
     * @return bool
     */
    public function create(array $data, $validate = true) {
        $fields = $this->_getFields();
        $_data = [];
        foreach ($fields as $f) {
            $f = $f->getName();
            if($this->_empty_to_null){
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

        return true;
    }
    
    /**
     * 清空
     * @return bool
     */
    public function clean(){
        $fields = $this->_getFields();
        foreach ($fields as $f) {
            $f = $f->getName();
            $this->$f = null;
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
            $data = $this->toArray();
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
                    if ($this->_checkScene($req['scene'])) {
                        if (!isset($data[$f]) || $data[$f] === '') {
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
                    if ($this->_checkScene($r['scene'])) {
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

    /**
     * 手术刀
     * 分析验证规则
     *
     */
    private function _scalpel() {
        $fields = false;
        try{
            $fields = $this->_getFields();
        }catch (\ReflectionException $e){
            $this->_errors[0] = $e;
        }
        if($fields){
            foreach ($fields as $f) {
                # 信息获取阶段
                $name = $f->getName(); # 字段名称
                $comment = $f->getDocComment(); # 字段规则注释
                # 默认正则结果
                $matches = null;
                if ($comment) {
                    # 正则筛选指令
                    preg_match_all('/@(default|rule|required|skip|ghost)(?:\[(\w+)\])?\s+?(.+)/', $comment, $matches);
                    $this->_validate[$name] = [];

                    # 跳过未有指令的内容
                    if (!$matches) {
                        continue;
                    }

                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $rn = trim($matches[1][$i]); # 指令名称
                        $rs = trim($matches[2][$i]); # 指令场景
                        $rc = trim($matches[3][$i]); # 规则内容

                        switch ($rn) {
                            # 跳过
                            case 'skip':
                                if (!isset($this->_validate[$name]['skip'])) {
                                    $this->_validate[$name]['skip'] = [];
                                }
                                $this->_validate[$name]['skip'][] = [
                                    'scene' => $rs
                                ];
                                break;
                            # 鬼魂字段
                            case 'ghost':
                                if (!isset($this->_validate[$name]['ghost'])) {
                                    $this->_validate[$name]['ghost'] = [];
                                }
                                $this->_validate[$name]['ghost'][] = [
                                    'scene' => $rs
                                ];
                                break;
                            # 默认值
                            case 'default':
                                $rc = explode(':', $rc, 2);
                                $t = trim($rc[0]); # 类型:int,float,null,string
                                $v = isset($rc[1]) ? trim($rc[1]) : null; # 值

                                if (!is_null($v)) {
                                    switch ($t) {
                                        case 'int':
                                            $v = intval($v);
                                            break;
                                        case 'float':
                                            $v = floatval($v);
                                            break;
                                        case 'null':
                                            $v = null;
                                            break;
                                        case 'func':
                                            $v = call_user_func($v);
                                            break;
                                        case 'method':
                                            $v = call_user_func_array([$this, $v], []);
                                            break;
                                        case 'array':
                                            $v = json_decode($v, true);
                                            break;
                                        case 'bool':
                                            $v = $v === 'true' ? true : false;
                                            break;
                                        default:
                                            $v = strval($v);
                                            break;
                                    }

                                    if (!isset($this->_validate[$name]['default'])) {
                                        $this->_validate[$name]['default'] = [];
                                    }
                                    $this->_validate[$name]['default'][] = [
                                        'content' => $v,
                                        'scene' => $rs
                                    ];
                                }

                                break;

                            # 规则
                            case 'rule':
                                $rc = explode('|', $rc, 2);
                                $rc[0] = trim($rc[0]);

                                $rca = explode(',', $rc[0]);
                                if(count($rca) < 2){
                                    $rca = explode(':',$rc[0]);
                                }
                                $this->_rck = isset($rca[0]) ? trim($rca[0]) : '';
                                $this->_rcs = isset($rca[1]) ? trim($rca[1]) : '';

//                            foreach ($rca as $k => $o){
//                                if($k == 0){
//                                    continue;
//                                }
//                                $o = explode(':', $o, 2);
//                                $this->_rco[$o[0]] = $o[1];
//                            }

                                $rule = [];
                                switch (true) {
                                    case $this->_rck === 'func': # 调用函数验证,传入当前字段的值
                                        $rule['content'] = $this->_rcs;
                                        break;
                                    case $this->_rck === 'method': # 调用实例方法验证,传入当字段名称和值
                                        $rule['content'] = [$this, $this->_rcs];
                                        break;
                                    default: # 默认调用验证库
                                        $rule['content'] = Filter::factory($rc[0]);
                                }

                                $rule['error'] = isset($rc[1]) ? $rc[1] : "{$name}格式不正确";
                                $rule['scene'] = $rs;

                                # 初始化规则部分
                                if (!isset($this->_validate[$name]['rule'])) {
                                    $this->_validate[$name]['rule'] = [];
                                }
                                $this->_validate[$name]['rule'][] = $rule;

                                break;

                            # 必填字段
                            case 'required':
                                $rc = explode('|', $rc);
                                # 初始化规则部分
                                if (!isset($this->_validate[$name]['required'])) {
                                    $this->_validate[$name]['required'] = [];
                                }
                                $this->_validate[$name]['required'][] = [
                                    'content' => true,
                                    'scene' => $rs,
                                    'error' => isset($rc[1]) ? $rc[1] : "{$name}不能为空",
                                ];
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 反射获取对象属性
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    private function _getFields() {
        $class = new \ReflectionClass($this);
        return $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * 设置字段默认值
     */
    private function _setDefault() {
        if ($this->_validate) {
            foreach ($this->_validate as $f => $v) {
                if (isset($v['default'])) {
                    foreach ($v['default'] as $def) {
                        if ($this->_checkScene($def['scene'])) {
                            $this->$f = $def['content'];
                        }
                    }
                }
            }
        }
    }

    /**
     * 检查是适用当前场景
     * @param $scene
     * @return bool
     */
    private function _checkScene($scene) {
        # 如果设置了当前场景,那么当前场景的设置或者未指定场景的指令会被应用
        # 否者,只有未指定场景的指令会被应用
        return $scene == '' || $this->_scene == $scene;
    }

    /**
     * 是否为魔鬼字段
     * @param $field
     * @return bool
     */
    private function _isGhostField($field) {
        if (isset($this->_validate[$field]['ghost'])) {
            foreach ($this->_validate[$field]['ghost'] as $v) {
                if ($this->_checkScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 是否为跳过的字段
     * @param $field
     * @return bool
     */
    private function _isSkipField($field) {
        if (isset($this->_validate[$field]['skip'])) {
            foreach ($this->_validate[$field]['skip'] as $v) {
                if ($this->_checkScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 添加错误
     * @param string $field
     * @param string $error
     */
    private function _addError($field, $error) {
        $error = explode(':',$error);
        $this->_errors[$field] = $error[0];
        $this->_codes[$field] = isset($error[1]) ? $error[1] : '500';
    }

}
