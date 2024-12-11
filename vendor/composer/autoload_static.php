<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2548468b50819cf3b1b49f33471414a4
{
    public static $files = array (
        'a4ecaeafb8cfb009ad0e052c90355e98' => __DIR__ . '/..' . '/beberlei/assert/lib/Assert/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Assert\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Assert\\' => 
        array (
            0 => __DIR__ . '/..' . '/beberlei/assert/lib/Assert',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2548468b50819cf3b1b49f33471414a4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2548468b50819cf3b1b49f33471414a4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit2548468b50819cf3b1b49f33471414a4::$classMap;

        }, null, ClassLoader::class);
    }
}
