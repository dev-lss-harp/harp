<?php
namespace Harp\enum;

enum PathEnum
{
    case PATH_FRAMEWORK;
    case PATH_PROJECT;
    case PATH_APP ;
    case PATH_PUBLIC;
    case PATH_PUBLIC_LAYOUTS;
    case PATH_PUBLIC_TEMPLATES;
    case PATH_PUBLIC_LAYOUTS_APP;
    case PATH_PUBLIC_TEMPLATES_APP;
    case PATH_PUBLIC_LAYOUTS_MODULE;
    case PATH_PUBLIC_TEMPLATES_MODULE;
    case PATH_PUBLIC_LAYOUTS_GROUP;
    case PATH_PUBLIC_TEMPLATES_GROUP;
    case PATH_VIEW_APP;
    case PATH_STORAGE;
}