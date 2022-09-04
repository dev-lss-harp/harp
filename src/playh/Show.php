<?php
namespace Harp\playh;

class Show
{
    private static $helper = 
    [
        "\033[34m [create app] => \033[0m \033[32m php play-h build::app --args={path of app} (Ex: --args=web/home home is module and web is app) \033[0m",
        "\033[34m [create api] => \033[0m \033[32m php play-h build::api --args={appName}/{moduleName}/{controllerName} (Ex: --args=api/v1/home) \033[0m",
        "\033[34m [create controller] => \033[0m \033[32m php play-h build::controller {name of controller with namespace} (Ex: play-h build::controller app/module/controller) \033[0m",
        "\033[34m [create model] => \033[0m \033[32m php play-h build::model {name of model with namespace} (Ex: play-h build::view app/module/model) \033[0m",
        "\033[34m [create view] => \033[0m \033[32m php play-h build::view {name of view with namespace} --layout=nameLayout (Ex: play-h build::view app/module/view) (Ex2: --layout optional parameter)  \033[0m",
        "\033[34m [create group] => \033[0m \033[32m php playh build::group {app}/{module}/{controller} --type=api --short=v1/oauth \033[0m",
        "\033[34m [create layout] => \033[0m \033[32m php play-h build::layout {name my layout} --file=index --folder=shared (note: --file and --folder are optionals.)\033[0m",
        "\033[34m [create layout file] => \033[0m \033[32m  php play-h build::layout_file --file={name my file} --path={my path} (Ex: --path=app/myFolderLayout/myFolderSection) \033[0m",
        "\033[34m [create storage] => \033[0m \033[32m  php play-h build::storage --app={name app} --chmod=0600 --subfolder=keys --chmodsub=0755 \033[0m",
        "\033[34m [delete app] => \033[0m \033[32m  php play-h del::app {name of app} \033[0m",
        "\033[34m [delete layout] => \033[0m \033[32m  php play-h del::layout {name of layout} \033[0m",
        "\033[34m [delete controller] => \033[0m \033[32m  php play-h del::controller {name of controller} \033[0m",
        "\033[34m [delete model] => \033[0m \033[32m  php play-h del::model {name of model} \033[0m",
        "\033[34m [delete view] => \033[0m \033[32m  php play-h del::view {name of view} \033[0m",
        "\033[34m [create migrate] => \033[0m \033[32m  php playh db::create_migration --app={name of app} --name={name of migrate} --table={name of table} \033[0m",
        "\033[34m [create key] => \033[0m \033[32m  php playh build::key --app=multitenacy --force=true \033[0m",
        "\033[34m [create cert] => \033[0m \033[32m  php playh build::cert --app=multitenacy --force=true \033[0m",
        
    ];

    private static $msgs = 
    [
        000 => "\033[31m 000 => Syntax error, command {%s} not found \033[0m".PHP_EOL,
        001 => "\033[31m 001 => The command expects {%s} argument, missing argument: %s. \033[0m".PHP_EOL,
        002 => "\033[31m 002 => Invalid number of arguments for command: {%s}. \033[0m".PHP_EOL,
        003 => "\033[31m 003 => Syntax error, in command {%s}! \033[0m".PHP_EOL,
        200 => "\033[32m 200 => Sucessfull created {%s} {%s}. \033[0m".PHP_EOL,
        500 => "\033[31m 500 => Error on created {%s} {%s}. \033[0m".PHP_EOL,
        501 => "\033[32m 501 => Sucessfull deleted {%s} {%s}. \033[0m".PHP_EOL,
        900 => "\033[31m 900 => Message is not defined, please check list of messages defined. \033[0m".PHP_EOL,
        1000 => "\033[31m 1000 => %s \033[0m".PHP_EOL,
        1001 => "\033[32m 1001 => %s \033[0m".PHP_EOL,
    ];

    public static function showMessage($msg,$noExit = false)
    {
        print($msg);
        print(PHP_EOL);
        if(!$noExit){  exit(); }
       
    }

    public static function helper()
    {
        self::showMessage(implode(PHP_EOL,self::$helper));
    }


    public static function getMessage($code)
    {
        if(!isset(self::$msgs[$code]))
        {
            self::showMessage(self::getMessage(900));
        }

        return self::$msgs[$code];
    }
}