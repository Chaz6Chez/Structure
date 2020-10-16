<?php
/**
 * Who ?: Chaz6chez
 * How !: 250220719@qq.com
 * Where: http://chaz6chez.top
 * Time : 2018/9/7|0:30
 * What : Creating Fucking Bug For Every Code
 */

# 解析
#
# @rule[login] func:_check|XXXXXX
#    ↓     ↓        ↓         ↓
# 验证规则  ↓        ↓         ↓
#       执行场景     ↓         ↓
#                验证方式      ↓
#                           提示语

# 验证标签
#
# @default num:7                  ->  若空值则默认为num类型7
# @required true|XXXXX            ->  该值为空时,提示XXXXX
# @skip                           ->  跳过验证(不执行该字段所有限制条件,toArray()默认输出，toArray(true)时过滤)
# @ghost                          ->  跳过输出(执行限制条件,toArray输出过滤该字段)
# @rule string,min:10,max:20|XXXX ->  验证规则,使用filter库/使用方法/使用实例验证规则

# 说明
#
# 1.所有验证标签均可使用[XXX]场景化区分.
# 2.filter库可修改库类文件提供默认验证规则.
# 3.修改Handle下类库文件的_defaultOptions数据,可以更改默认规则.
# 4.default标签可使用func和method,与rule区别是,
# rule使用其返回true or false来进行判断,default直接使用其返回值.

require_once dirname(__DIR__) . '/vendor/autoload.php';
function is_assoc($array){
    return boolval(array_keys($array) !== range(0, count($array) - 1));
}

try {
    $support = \Example\User::factory();
//    $support = \Example\User2::factory();
    var_dump($support->getRco());
}catch(Exception $exception){
    echo $exception->getMessage();
}


//$support->age = '123[>]|456[<]';
//$support->sex = '1';
////var_dump($support);
//echo json_encode(
//    $support->outputArrayUseMapping($support::FILTER_STRICT)
//);
