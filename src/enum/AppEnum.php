<?php
namespace Harp\enum;

enum AppEnum: string
{
    case APP_DIR = 'app'; 
    case APP_NAMESPACE = '\\App';
    case APP_NAME = '__APP_NAME';
    case ENV_MAINTAINER = '.env-maintainer';
    case ENV_DEVELOP = '.env-develop';
    case ENV = '.env';
    case StorageDir = 'storage';
    case StorageCertsDir = 'certs';
    case StorageKeysDir = 'keys';
    case PublicKey = 'App::PublikKey';
    case PrivateKey = 'App::PrivateKey';
    case HashApp =  'App::HashApp';
    case EncryptionKey =  'App::EncryptionKey';
}