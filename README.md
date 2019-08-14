# structure

## 更新内容

##### 2019-08-14 
 - 【移除】toArray方法群
 - 【调整】类结构优化，内部方法表达调整
 - 【调整】将常量标准参移至Helper/Keys
 - 【新增】@operator标签

## v2.0.0

有什么好的建议和想法，请联系250220719@qq.com
***
## 使用场景
 - 接口入参、出参的验证筛选
 - 服务组件的入参结构体
 - 数据库映射类

## 解析
````
 @验证方式[场景] 验证方式|错误提示语:错误码
       ↓           ↓         ↓     ↓
 @rule[check] string,min:1|XXXXXX:500                              
````
***
## 标签
| 标签名 | 方式 | 说明|
| :---: | :---:| :--- |
| @default | 类型、func、method | func与method是将返回值默认赋予该标签 |
| @required| true | 判断是否为必要值 |
| @rule | 类型、func、method | 以func与method的bool返回类型判断验证 |
| @skip | 无 | 跳过验证 |
| @ghost| 无 | 跳过输出 |
| @key| 无 | 配合标记输出 |
| @operator| 无 | 配合输出过滤/装载 |

````
/**
 * @default num:7                  ->  若空值则默认为num类型7 
 * @required true|XXXXX            ->  该值为空时,提示XXXXX
 * @skip                           ->  跳过验证(不执行该字段所有限制条件,toArray()默认输出，toArray(true)时过滤)
 * @ghost                          ->  跳过输出(执行限制条件,toArray输出过滤该字段)
 * @rule string,min:10,max:20|XXXX ->  验证规则,使用filter库/使用方法/使用实例验证规则
 * @key                            ->  输出标记
 * @operator                       ->  装载/过滤标记
 */
````
***
## 方法

| 方法名 | 参数 | 说明 |
| :---: | :---: | :---| 
|   factory($data,$scene)| data:数据(可选) scene:场景(可选) | 实例化方法,可加载数据和场景 |
|   setScene($scene)| scene:场景 | 设置场景,在验证方法之前调用有效 |
|   outputArrayByKey($filterNull,$scene)| filterNull:是否过滤空值(可选) scene:场景 | 数据以数组形式输出（不过滤空字符串） |
|   create($data,$validate)| data:数据 validate:是否执行验证(可选) | 输入数据,可执行验证 |
|   validate($data)| data:数据(可选) | 验证器方法,可加载数据 |
|   hasError($filed)| filed:条件(可选) | 错误确认，返回布尔 |
|   getError()| 无 | 获取第一条错误信息 |
|   getErrors()| 无 | 以数组形式获取所有错误信息 |
|   getCode()| 无 | 获取第一条错误码 |
|   getCodes()| 无 | 以数组形式获取所有错误码 |
|   clean()| 无 | 初始化已创建的struct |
 
***
## 例子
- 方式一
````    
        $data = [
            'a' => '',
            'b' => '1',
        ];
        
        $structure = \Test\Check::factory();
        $structure->setScene('login');
        $structure->create($data,true);
        if($structure->hasError()){
            $this->response->error($structure->getError());
        }
        $this->response->success($structure->toArray());

````
