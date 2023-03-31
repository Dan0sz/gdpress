<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita580b00d9bd25d96641eb4439d739601
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Gdpress\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Gdpress\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita580b00d9bd25d96641eb4439d739601::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita580b00d9bd25d96641eb4439d739601::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita580b00d9bd25d96641eb4439d739601::$classMap;

        }, null, ClassLoader::class);
    }
}
