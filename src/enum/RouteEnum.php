<?php
namespace Harp\enum;

enum RouteEnum : string
{
    case App = 'Route::App';
    case Current = 'Route::Current';
    case Path = 'Route::Path';
    case Alias = 'Route::Alias';
    case Module = 'Route::Module';
    case Group = 'Route::Group';
    case Controller = 'Route::Controller';
    case ControllerPath = 'Route::ControllerPath';
    case Action = 'Route::Action';
    case AppKeyDefault =  'Route::AppKeyDefault';
}