<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Structure;
use Structure\Helper\StructureException;

abstract class Filter {

    protected static $_filters = [
        'array'  => 'Structure\Handle\Arrays',
        'bool'   => 'Structure\Handle\Booleans',
        'float'  => 'Structure\Handle\Floats',
        'int'    => 'Structure\Handle\Ints',
        'ip'     => 'Structure\Handle\IP',
        'object' => 'Structure\Handle\Object',
        'string' => 'Structure\Handle\Strings',
        'pool'   => 'Structure\Handle\Pool',    # 未完善
        'map'    => 'Structure\Handle\Map',
        'url'    => 'Structure\Handle\URL',
        'regex'  => 'Structure\Handle\Regex',
        'chain'  => 'Structure\Handle\Chain',   # 未完善
    ];

    protected $_filterName = '';

    protected $_defaultOptions = [];

    protected $_options = [];

    /**
     * Struct constructor.
     * @param array $options
     */
    final public function __construct(array $options = []) {
        $this->setOptions($options);
    }

    /**
     * 过滤器
     * @param $var
     * @return mixed
     */
    abstract public function filter($var);

    /**
     * 验证器
     * @param $var
     * @return bool
     */
    public function validate($var) {
        $filtered = $this->filter($var);
        return !is_null($filtered) && $filtered == $var;
    }

    /**
     * 工厂入口
     * @param $filter string 例：string,max:1
     * @return mixed
     */
    /**
     * @param $filter
     * @return mixed
     * @throws StructureException
     */
    public static function factory($filter) {
        # 判断继承
        if ($filter instanceof self) {
            return $filter;
        }
        # 开始分析
        $res = static::parse($filter);
        $filterName = $res[0];
        $options = $res[1];
        if (!isset(self::$_filters[$filterName])) {
            throw new StructureException(StructureException::INVALID_FILTER_SPECIFIED . $filter);
        }
        $class = self::$_filters[$filterName];
        # 返回实例
        return new $class($options);
    }

    /**
     * 注册
     * @param $name
     * @param $class
     */
    public static function register($name, $class) {
        if (!is_subclass_of($class, __CLASS__)) {
            throw new StructureException(StructureException::CLASS_MUST_BE_HANDLE_INSTANCE);
        }
        self::$_filters[strtolower($name)] = $class;
    }

    /**
     * 获取配置
     * @return array
     */
    public function getOptions() {
        return $this->_options;
    }

    /**
     * 设置(单)
     * @param $key
     * @param $value
     * @return $this
     */
    public function setOption($key, $value) {
        $this->_options[$key] = $value;
        return $this;
    }

    /**
     * 设置(数组方式)
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options) {
        $this->_options = array_merge($this->_defaultOptions,$options);
        return $this;
    }
    
    /**
     * 分析器
     * @param $filter
     * @return array
     */
    protected static function parse($filter) {
        $parts = explode(',', $filter);
        $filterName = strtolower(array_shift($parts));
        $options = [];
        //todo 加入特殊验证
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            $partArr = explode(':', $part, 2);
            $options[$partArr[0]] = $partArr[1];
        }
        return [$filterName,$options];
    }
}
