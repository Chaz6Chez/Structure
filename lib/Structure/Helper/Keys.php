<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2019/6/24            #
# -------------------------- #

namespace Structure\Helper;

class Keys {
    # 数值过滤
    const OPERATER_CLOASE        = 0; # 默认关闭
    const OPERATER_LOAD_OUTPUT   = 1; # 装载输出
    const OPERATER_FILTER_OUTPUT = 2; # 过滤输出
    # 参数过滤 []
    const FILTER_NORMAL = 0; # 默认不过滤
    const FILTER_NULL   = 1; # 过滤NULL
    const FILTER_EMPTY  = 2; # 过滤空字符串
    const FILTER_STRICT = 3; # 严格过滤
    const FILTER_KEY    = 4; # 仅输出KEY字段
    # 输出转换
    const OUTPUT_NORMAL = 0; # 默认输出
    const OUTPUT_NULL   = 1; # 空字符串转NULL
    const OUTPUT_EMPTY  = 2; # NULL转空字符串
    const OUTPUT_KEY    = 3; # 仅输出KEY字段
}