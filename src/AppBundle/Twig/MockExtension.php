<?php

namespace AppBundle\Twig;

class MockExtension extends \Twig_Extension
{
    public function __construct() {}

    public function getName()
    {
        return 'MockExtension';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_content', array($this, 'getContent')),
            new \Twig_SimpleFunction('render', function($toRender, $data = [])
            {
                if (is_array($toRender)) {
                    return call_user_func_array($toRender, $data);
                } else {
                    $templateName = $toRender;
                    \View::render($templateName, $data);
                }
            }),
            new \Twig_SimpleFunction('get_image', function($imageName)
            {
                return "<img src='images/{$imageName}' alt='{$imageName}' />";
            }),
            new \Twig_SimpleFunction('path', function($routeName)
            {
                return \Router::makeUrlTo($routeName);
            }),
            new \Twig_SimpleFunction('get_club', function()
            {
                return [];
            }),
            new \Twig_SimpleFunction('asset', function($assetName)
            {
                return \Environment::get("ASSET_BASE")."/{$assetName}";
            }),
            new \Twig_SimpleFunction('is_granted', function($role)
            {
                return true;
            }),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('purify', function($string) {
                return $string;
            }),
            new \Twig_SimpleFilter('localizeddate', function($date)
            {
                error_log("localizeddate({$date})");
                return $date;
            }),
        );
    }


    public function getContent($stringId)
    {
        return "get_content({$stringId})";
    }
}
