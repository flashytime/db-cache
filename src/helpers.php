<?php
/**
 * Created by IntelliJ IDEA.
 * Author: flashytime
 * Date: 2015/5/20 14:42
 */

/**
 * @param  array $array
 * @param  string $key
 * @param  mixed $default
 * @return mixed
 */
if (!function_exists('array_get')) {
    function array_get($array, $key, $default = null)
    {
        return (isset($array[$key]) && !empty($array[$key])) ? $array[$key] : $default;
    }
}

/**
 * @param  array $array
 * @param  string $key
 * @return boolean
 */
if (!function_exists('array_has')) {
    function array_has($array, $key)
    {
        return isset($array[$key]) && !empty($array[$key]);
    }
}

/**
 * 把数组的key全部变成小写
 * @param array $array
 * @return array
 */
if (!function_exists('array_key_lower')) {
    function array_key_lower(array $array)
    {
        $lowerArr = [];
        foreach ($array as $key => $val) {
            $lowerArr[strtolower($key)] = $val;
        }

        return $lowerArr;
    }
}