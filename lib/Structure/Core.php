<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2019/8/14            #
# -------------------------- #
namespace Structure;

use Structure\Helper\Keys;

abstract class Core {
    protected $_emptyOrNull     = true;
    protected $_operator        = Keys::OPERATER_CLOASE;
    protected $_operatorNeedTag = true;
    protected $_keys            = null;

    /**
     * @var string 操作者正则 [用于特殊赋值的过滤和操作] [column仅做了包含性判断]
     */
    private $_operatorPreg = '/(?<column>[\s\S]*(?=\[(?<operator>\+|\-|\*|\/|\>\=?|\<\=?|\!|\<\>|\>\<|\!?~)\]$)|[\s\S]*)/';
    /**
     * @var string 手术刀正则 [注解]
     */
    private $_scalpelPreg  = '/@(default|rule|required|skip|ghost|key|operator)(?:\[(\w+)\])?\s+?(.+)/';

    protected $_validate = [];
    protected $_errors   = [];
    protected $_codes    = [];
    protected $_scene    = '';
    protected $_rck      = ''; # rule content key
    protected $_rcs      = ''; # rule content string
    protected $_rco      = []; # rule content options

    /**
     * Core constructor.
     * @param null $data
     * @param string $scene
     */
    public function __construct($data = null, $scene = '') {
        $this->_core_scalpel();                  # 加载手术刀
        $this->_core_setDefault();               # 加载默认值

        if($scene){
            $this->setScene($scene);             # 加载场景
        }
        if ($data and is_array($data)) {
            $this->create($data, false); # 创建数据
        }
    }

    public static function factory($data = null, $scene = '') {
        $cls = get_called_class();
        return new $cls($data, $scene);
    }

    /**
     * 手术刀
     * 分析验证规则
     */
    private function _core_scalpel() {
        $fields = false;
        try{
            $fields = $this->_core_getFields();
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
                    preg_match_all($this->_scalpelPreg, $comment, $matches);
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
                            # 鬼魂字段
                            case 'ghost':
                            # key字段
                            case 'key':
                            # operator字段
                            case 'operator':
                                $this->_core_setValidate($rn,$name,$rs);
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

                                    $this->_core_setValidate($rn,$name,[
                                        'content' => $v,
                                        'scene' => $rs
                                    ],false);
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

                                # 设置Validate
                                $this->_core_setValidate($rn,$name,$rule,false);

                                break;

                            # 必填字段
                            case 'required':
                                $rc = explode('|', $rc);

                                # 设置Validate
                                $this->_core_setValidate($rn,$name,[
                                    'content' => true,
                                    'scene' => $rs,
                                    'error' => isset($rc[1]) ? $rc[1] : "{$name}不能为空",
                                ],false);
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 设置验证内容
     * @param $tag
     * @param $name
     * @param $value
     * @param bool $isScene 是否仅传递scene
     */
    private function _core_setValidate($tag,$name,$value,$isScene = true){
        if (!isset($this->_validate[$name][$tag])) {
            $this->_validate[$name][$tag] = [];
        }
        $this->_validate[$name][$tag][] = $isScene ? [
            'scene' => $value
        ] : $value;
    }

    /**
     * 过滤正则辅助
     * @param $value
     * @return mixed
     */
    private function _core_operatorPreg($value){
        preg_match($this->_operatorPreg, $value, $match);
        return $match;
    }

    /**
     * 反射获取对象属性
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    private function _core_getFields() {
        $class = new \ReflectionClass($this);
        return $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * 设置字段默认值
     */
    private function _core_setDefault() {
        if ($this->_validate) {
            foreach ($this->_validate as $f => $v) {
                if (isset($v['default'])) {
                    foreach ($v['default'] as $def) {
                        if ($this->_validateScene($def['scene'])) {
                            $this->$f = $def['content'];
                        }
                    }
                }
            }
        }
    }

    abstract public function validate(array $data = null);

    abstract public function create(array $data, $validate = true);

    abstract public function clean();

    abstract public function hasError($filed = null);

    abstract public function getError();

    abstract public function getCode();

    abstract public function getErrors();

    abstract public function getCodes();

    /**
     * @return Keys
     */
    public function Keys(){
        if(!$this->_keys instanceof Keys){
            return $this->_keys = new Keys();
        }
        return $this->_keys;
    }

    public function setEmptyOrNull(bool $bool){
        $this->_emptyOrNull = $bool;
        return $this;
    }

    public function getEmptyOrNull(){
        return $this->_emptyOrNull;
    }

    public function setOperator(int $operator,bool $needTag = true){
        $this->_operator        = $operator;
        $this->_operatorNeedTag = $needTag;
        return $this;
    }

    public function getOperator(){
        return $this->_operator;
    }

    public function settingReset(){
        $this->_emptyOrNull     = true;
        $this->_operator        = Keys::OPERATER_CLOASE;
        $this->_operatorNeedTag = true;
    }

    public function setScene($scene) {
        $this->_scene = $scene;
        return $this;
    }

    public function getScene(){
        return $this->_scene;
    }

    public function getFields(){
        try{
            return $this->_core_getFields();
        }catch (\ReflectionException $exception){
            return false;
        }
    }

    /**
     * 分析operator
     * @param $key
     * @param $value
     * @return array
     *
     * key   = array[0]
     * value = array[1]
     */
    protected function _parsingOperator($key,$value){
        if(
            $value and
            $this->_isOperatorField($key)
        ){
            switch ($this->_operator){
                case Keys::OPERATER_LOAD_OUTPUT:
                    $valueArr = explode('|',$value);
                    if(count($valueArr) > 1){
                        foreach ($valueArr as $value){
                            $match = $this->_core_operatorPreg($value);
                            if(isset($match['operator'])){
                                $key = "{$key}[{$match['operator']}]";
                                $value = $match['column'];
                            }
                        }
                    }else{
                        $match = $this->_core_operatorPreg($value);
                        if(isset($match['operator'])){
                            $key = "{$key}[{$match['operator']}]";
                            $value = $match['column'];
                        }
                    }
                    break;

                case Keys::OPERATER_FILTER_OUTPUT:
                    $match = $this->_core_operatorPreg($value);
                    if(isset($match['column'])){
                        $value = $match['column'];
                    }
                    break;

                case Keys::OPERATER_CLOASE:
                default:

                    break;
            }
        }
        return [$key,$value];
    }

    /**
     * 检查是适用当前场景
     * @param $scene
     * @return bool
     */
    protected function _validateScene($scene) {
        # 如果设置了当前场景,那么当前场景的设置或者未指定场景的指令会被应用
        # 否者,只有未指定场景的指令会被应用
        return $scene == '' or $this->_scene == $scene;
    }

    /**
     * 是否为魔鬼字段
     * @param $field
     * @return bool
     */
    protected function _isGhostField($field) {
        if (isset($this->_validate[$field]['ghost'])) {
            foreach ($this->_validate[$field]['ghost'] as $v) {
                if ($this->_validateScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * 是否符合operator
     * @param $field
     * @return bool
     */
    protected function _isOperatorField($field) {
        if(!$this->_operatorNeedTag){
            return true;
        }
        if (isset($this->_validate[$field]['operator'])) {
            foreach ($this->_validate[$field]['operator'] as $v) {
                if ($this->_validateScene($v['scene'])) {
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
    protected function _isSkipField($field) {
        if (isset($this->_validate[$field]['skip'])) {
            foreach ($this->_validate[$field]['skip'] as $v) {
                if ($this->_validateScene($v['scene'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 是否为key的字段
     * @param $field
     * @param string $scene
     * @return bool
     */
    protected function _isKeyField($field,$scene = '') {
        if (isset($this->_validate[$field]['key'])) {
            if($scene){
                foreach ($this->_validate[$field]['key'] as $v) {
                    if ($v['scene'] == '' or $v['scene'] == $scene) {
                        return true;
                    }
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 添加错误
     * @param string $field
     * @param string $error
     */
    protected function _addError($field, $error) {
        $error = explode(':',$error);
        $this->_errors[$field] = $error[0];
        $this->_codes[$field] = isset($error[1]) ? $error[1] : '500';
    }
}