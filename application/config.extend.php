<?php
/**
 * 扩展配置文件
 */
return [

    //BOF 用户权限
    'admin_power' => [
        'admin' => 1,   //管理员
        'leader' => 2,//组长
        'operator' => 3,//操作员
        'shop_boss' => 4,//加盟老板
        'shop_manager' => 5,//加盟店店长
        'shop_employee' => 6,//加盟店员工
        'shop_dz' => 5,//加盟店店长
        'shop_yg' => 6,//加盟店员工
    ],
    
    'admin_power_shop' => [
        'shop_boss' => 4,//加盟老板
        'shop_manager' => 5,//加盟店店长
        'shop_employee' => 6,//加盟店员工
        'shop_dz' => 5,//加盟店店长
        'shop_yg' => 6,//加盟店员工
    ],
    'admin_power_name' => [
        1 => '管理员',   //管理员
        2 => '组长',//组长
        3 => '操作员',//操作员
        4 => '加盟店老板',   //加盟店老板
        5 => '加盟店店长',//加盟店店长
        6 => '加盟店员工',//加盟店员工
    ],
    //EOF 用户权限

    //BOF 性别
    'gender' => [
        'm' => '男士',
        'f' => '女士',
    ]
];