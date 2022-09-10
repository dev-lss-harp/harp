<?php
namespace Harp\enum;

enum ViewEnum : string
{
    case Resources = 'View::Resources';
    case ServerVar = "View::ServerVar";
    case Action = "View::Action";
    case Group = "View::Group";
    case RouteCurrent = 'View::RouteCurrent';
    
    case FlagProp = 'prop';
    case FlagConst = 'const';
    case FlagPath = 'path';
    case FlagContent = 'content';
}