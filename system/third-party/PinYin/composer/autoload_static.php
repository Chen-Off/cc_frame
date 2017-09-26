<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit15642dc44f33b5fa17b11d1f755ee9ef
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'Overtrue\\Pinyin\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Overtrue\\Pinyin\\' => 
        array (
            0 => __DIR__ . '/..' . '/overtrue/pinyin/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit15642dc44f33b5fa17b11d1f755ee9ef::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit15642dc44f33b5fa17b11d1f755ee9ef::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
