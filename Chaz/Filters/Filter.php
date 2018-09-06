<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
namespace Chaz\Filters;

abstract class Filter {

    const INVALID_FILTER_SPECIFIED = '无效的过滤器';
    const CLASS_ERROR = '类名必须是Handle的实例';

    protected static $filters = [
        'array'  => 'Chaz\Filters\Handle\Arrays',
        'bool'   => 'Chaz\Filters\Handle\Booleans',
        'float'  => 'Chaz\Filters\Handle\Floats',
        'int'    => 'Chaz\Filters\Handle\Ints',
        'ip'     => 'Chaz\Filters\Handle\IP',
        'object' => 'Chaz\Filters\Handle\Object',
        'string' => 'Chaz\Filters\Handle\Strings',
        'pool'   => 'Chaz\Filters\Handle\Pool',
        'map'    => 'Chaz\Filters\Handle\Map',
        'url'    => 'Chaz\Filters\Handle\URL',
        'regex'  => 'Chaz\Filters\Handle\Regex',
    ];

    protected $defaultOptions = [];

    protected static $options = [];

    protected static $filterName;

    /**
     * Filters constructor.
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
    public static function factory($filter) {
        # 判断继承
        if ($filter instanceof self) {
            return $filter;
        }
        # 开始分析
        static::parse($filter);

        if (!isset(self::$filters[self::$filterName])) {
            throw new \InvalidArgumentException(self::INVALID_FILTER_SPECIFIED . ': ' . $filter);
        }
        $class = self::$filters[self::$filterName];
        # 返回实例
        return new $class(self::$options);
    }

    /**
     * 注册
     * @param $name
     * @param $class
     */
    public static function register($name, $class) {
        if (!is_subclass_of($class, __CLASS__)) {
            throw new \InvalidArgumentException(self::CLASS_ERROR);
        }
        self::$filters[strtolower($name)] = $class;
    }

    /**
     * 获取配置
     * @return array
     */
    public function getOptions() {
        return self::$options;
    }

    /**
     * 设置(单)
     * @param $key
     * @param $value
     * @return $this
     */
    public function setOption($key, $value) {
        self::$options[$key] = $value;
        return $this;
    }

    /**
     * 设置(数组方式)
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options) {
        self::$options = array_merge($options, $this->defaultOptions);
        return $this;
    }
    /**
     * 分析器
     * @param $filter
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
        self::$filterName = $filterName;
        self::$options = $options;
    }
}
