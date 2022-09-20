<?php
namespace Harp\playh;

class Show
{
    private static $helper = 
    [
        PHP_EOL.PHP_EOL."\033[36m ============================================================================================= \033[0m",
        "\033[36m                                  Harp playh                                              ====\033[0m",
        "\033[36m =============================================================================================\033[0m".PHP_EOL,
        "\033[36m [legenda]: ".PHP_EOL."\033[91m * = command ".PHP_EOL."\033[92m * = arguments ".PHP_EOL."\033[093m * = optional \n\033[0m",

        "\033[36m [cli]               ".PHP_EOL."\033[0m \033[91m php playh \033[0m".PHP_EOL,
        "\033[36m [app build]         ".PHP_EOL."\033[0m \033[91m build::app \033[92m--args={appName}/{moduleName}\033[0m".PHP_EOL,
        "\033[36m [api build]         ".PHP_EOL."\033[0m \033[91m build::api \033[92m--args={appName}/{moduleName}/{controllerName} \033[0m".PHP_EOL,
        "\033[36m [controller build]  ".PHP_EOL."\033[0m \033[91m build::controller \033[92m{appName}/{moduleName}/{controllerName} \033[0m".PHP_EOL,
        "\033[36m [model build]       ".PHP_EOL."\033[0m \033[91m build::model \033[92m{appName}/{moduleName}/{modelName} \033[0m".PHP_EOL,
        "\033[36m [build view]        ".PHP_EOL."\033[0m \033[91m build::view \033[92m{appName}/{moduleName}/{viewName} \033[093m--layout={layoutName} \033[0m".PHP_EOL,
        "\033[36m [group build]       ".PHP_EOL."\033[0m \033[91m build::group \033[92m{appName}/{moduleName}/{controllerName} --type={api|app} --short={shortNameRoute} \033[0m".PHP_EOL,
        "\033[36m [layout build]      ".PHP_EOL."\033[0m \033[91m build::layout \033[92m{layoutName} \033[093m--file={fileName} --folder={folderName}\033[0m".PHP_EOL,
        "\033[36m [layout file build] ".PHP_EOL."\033[0m \033[91m build::layout_file \033[92m--file={fileName} --path={path} \033[093m(Ex: --path=app/dirLayout/dirSection) \033[0m".PHP_EOL,
        "\033[36m [storage build]     ".PHP_EOL."\033[0m \033[91m build::storage \033[92m--app={appName} --chmod={number} --subfolder={folderName} \033[0m".PHP_EOL,
        "\033[36m [app del]           ".PHP_EOL."\033[0m \033[91m del::app \033[92m{appName} \033[0m".PHP_EOL,
        "\033[36m [layout del]        ".PHP_EOL."\033[0m \033[91m del::layout \033[92m{layoutName} \033[0m".PHP_EOL,
        "\033[36m [controller del]    ".PHP_EOL."\033[0m \033[91m del::controller \033[92m{controllerName} \033[0m".PHP_EOL,
        "\033[36m [model del]         ".PHP_EOL."\033[0m \033[91m del::model \033[92m{modelName} \033[0m".PHP_EOL,
        "\033[36m [view del]          ".PHP_EOL."\033[0m \033[91m del::view \033[92m{viewName} \033[0m".PHP_EOL,
        "\033[36m [migration build]   ".PHP_EOL."\033[0m \033[91m db::create_migration \033[92m--app={appName} --name={migrationName} --(table|create)={tableName} \033[093m--order=number \033[0m\033[0m".PHP_EOL,
        "\033[36m [enity build]       ".PHP_EOL."\033[0m \033[91m db::entity \033[92m{app}/{module}/{entityName} \033[093m--table={nameTable} \033[0m\033[0m".PHP_EOL,
        "\033[36m [key build]         ".PHP_EOL."\033[0m \033[91m build::key \033[92m--app={appName} \033[093m--force=true \033[0m".PHP_EOL,
        "\033[36m [cert build]        ".PHP_EOL."\033[0m \033[91m build::cert \033[92m--app={appName} \033[093m--force=true \033[0m".PHP_EOL,
        "\033[36m [server build]      ".PHP_EOL."\033[0m \033[91m build::server \033[093m--port={number} \033[0m".PHP_EOL,
    ];

    private static $msgs = 
    [
        000 => "\033[31m 000 => Syntax error, command {%s} not found \033[0m".PHP_EOL,
        001 => "\033[31m 001 => The command expects {%s} argument, missing argument=>  %s. \033[0m".PHP_EOL,
        002 => "\033[31m 002 => Invalid number of arguments for command=>  {%s}. \033[0m".PHP_EOL,
        003 => "\033[31m 003 => Syntax error, in command {%s}! \033[0m".PHP_EOL,
        004 => "\033[92m %s => playh server starting at: %s! \033[0m".PHP_EOL,
        200 => "\033[92m 200 => Sucessfull created {%s} {%s}. \033[0m".PHP_EOL,
        500 => "\033[31m 500 => Error on created {%s} {%s}. \033[0m".PHP_EOL,
        501 => "\033[92m 501 => Sucessfull deleted {%s} {%s}. \033[0m".PHP_EOL,
        900 => "\033[31m 900 => Message is not defined, please check list of messages defined. \033[0m".PHP_EOL,
        1000 => "\033[31m 1000 => %s \033[0m".PHP_EOL,
        1001 => "\033[92m 1001 => %s \033[0m".PHP_EOL,
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