# 问题日志

【2017年9月26日】语言包载入，调用架构需要修改
------
完成时间：2017年9月29日 
详细：语言加载模式全部变更换为静态实例化模式。`Lang.php` 语言公共类 <br>
操作方式：


```
//设置语言风格
Lang::range('zh-cn');
//获取语言包
Lang::get(['faterArrKey', 'subArrKey', 'subKey']);
Lang::get('key');
Lang::get();
//获取模块语言包，可跨模块获取
$modules = '';
$modules = 'Text';
Lang::getM($modules);
//设置新的语言解释
Lang::set($name, $value = null, $range = '');
//检测语言内容是否存在
Lang::has($name, $range = '');

```

公共语言包：
>[-application](#-application)
>>lang.php
```
return [
    // +----------------------------------------------------------------------
    // 基本语言输出要素
    // +----------------------------------------------------------------------

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

```
return [
   'name' => '主模块名称',
   
    // 子模块
    'index' => [
        'name' => '首页', //子模块名称
        
        //功能模块
        'action' => [
            'index' => '概览',//功能模块名称KEY直接使用功能模块名称
            ],
    ],
]
```

时间：2017年9月26日 <br>
问题：公共类需要重置，使用静态加载方式
```
```
