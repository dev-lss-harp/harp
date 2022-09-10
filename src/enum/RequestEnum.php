<?php
namespace Harp\enum;

enum RequestEnum: string
{
    case ALL = '__ALL';
    case POST = '__POST';
    case GET = '__GET';
    case PUT = '__PUT';
    case PATCH = '__PATCH';
    case DELETE = '__DELETE';
}