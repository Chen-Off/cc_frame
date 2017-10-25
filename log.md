# 问题日志

【2017年10月7日】Db 基本类模拟ThinkPHP 优化操作，优化验证
------
完成时间：2017年10月19日 <br>
详细： <br>
新增加文件`system/library/db/Analyze.php`, 验证校验类 验证所传递的参数格式与内容是否正确<br>
自动校验表是否存在，数据库配置中设置开关校验表字段是否存在且在update和insert中所提交的数据是否正确【只适用于常规数据字符串等等】<br>
新增加部分操作
```
Db::table('admin')->field('*') // 指定查询字段
Db::table('admin')->comment('注释')  //注释
Db::table('admin')->force($force)  //指定强制索引
Db::table('admin')->having($having)  //查询条件限制
Db::table('admin')->distinct($having)  //去重
Db::table('admin')->lock(true)  //锁表【select 有效】
```
修改 where 和 whereOr 条件语句的书写设定
```
$where = 'id = 1';//字符串模式
$where = [
    'id = 1',//多条件数组字符串模式
    ['id' , '=',1], //多条件直接设定表达式模式['字段' , '表达式','参数']
];
```

【2017年9月26日】分页公共类设定
------
完成时间：2017年9月29日 <br>
详细：分页公共基类`system/library/Paginator.php` ，如果没有输出JSON 格式内容，默认自动执行渲染输出。<br>
操作方式：
```diff
+//设置列表项目的名称，不设定默认【项目】
Paginator::setItemName('顾客');
+//设置页码不带参数的URL,不设定将默认使用当前功能模块URL【brtUrl('action')】
Paginator::setUrl($url);
+//设置列表每页显示数量，不设定默系统值【config.php】
Paginator::setRows(30);

+//设置当前页码数
Paginator::setPageNow(1);
+//设置要查询的项目总数
Paginator::setTotal(1000);

-//注意：横排渲染调用顺序依照列表头设定。列表内容KEY值必须和列表头KEY值相同。可以使用数组默认排序KEY值
+//设置列表头
-//表头名称可以直接使用字符串空样式，也可以使用数组格式，设定名称，宽度，样式风格【class】
$header = [
    'name' => '名称',
    'age' => ['title' => '年龄', 'width' => '120', 'style' => 'text-center'],
];
Paginator::setListHead($header);

+//设置列表内容，
foreach($query as $v) {
    $data[$k] = [
        'name' => $v['name'],
        'age'   =>  $v['age'],
    ];
}
Paginator::setListData($data);

+//输出 JSON 所需要的数据，本次输出后，分页数据将重置
Paginator::pageJson();
```

【2017年9月26日】公共类需要重置，使用静态加载方式
------
完成时间：2017年9月29日 <br>
详细：公共基类`application/Common.class.php`移除 <br>

【2017年9月26日】语言包载入，调用架构需要修改
------
完成时间：2017年9月29日 <br>
详细：语言加载模式全部变更换为静态实例化模式。`system/library/Lang.php` 语言公共类 <br>
操作方式：


```diff
+//设置语言风格
Lang::range('zh-cn');
+//获取语言包
Lang::get(['faterArrKey', 'subArrKey', 'subKey']);
Lang::get('key');
Lang::get();
+//获取模块语言包，可跨模块获取
$modules = '';
$modules = 'Text';
Lang::getM($modules);
+//设置新的语言解释
Lang::set($name, $value = null, $range = '');
+//检测语言内容是否存在
Lang::has($name, $range = '');

```

公共语言包：
>[-application](#-application)
>>lang.php
```
return [
+// +----------------------------------------------------------------------
+// 基本语言输出要素
+// +----------------------------------------------------------------------

    //默认标题
    'meta_title' => '默认标题',
    //默认关键词
    'meta_keywords' => '默认关键词',
    //默认描述
    'meta_description' => '默认描述',
]
```

模块独立语言包：
>[-modules](#-modules)
>>[-controllers](#-controllers)
>>>[-Index](#-Index)
>>>>lang.php

```diff
return [
   'name' => '主模块名称',
   
    +// 子模块
    'index' => [
        'name' => '首页',+ //子模块名称
        
        //功能模块
        'action' => [
            'index' => '概览',+//功能模块名称KEY直接使用功能模块名称
            ],
    ],
]
```
