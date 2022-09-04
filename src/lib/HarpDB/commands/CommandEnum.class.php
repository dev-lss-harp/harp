<?php
/*
 * Copyright 2010 Leonardo Souza da Silva <allezo.lss@gmail.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Harp\lib\HarpDB;

use bin\enm\HarpEnum;

class CommandEnum extends HarpEnum
{
    const INT = 'int';
    const FLOAT = 'float';
    const DOUBLE = 'double';
    const VARCHAR = 'varchar';
    const TEXT = 'text';
    const DATE_ISO8601 = 'date';
    const TIMESTAMP = 'timestamp';
    const DATETIME = 'datetime';
    const TIME = 'time';
    const BOOLEAN = 'boolean';
    const BIT = 'bit';    
    const VARCHAR_NO_QUOTES = '7';
    
    const TYPE_TINYINT = 127;
    const TYPE_SMALLINT = 32767;
    const TYPE_MEDIUMINT = 8388607;
    const TYPE_INT = 2147483647;
    const TYPE_BIGINT = 9223372036854775807;
    const TYPE_FLOAT = 2147483647.9999999;
    const TYPE_DOUBLE = 9223372036854775807.9999999999999999;
    const TYPE_CHAR = 255;
    const TYPE_VARCHAR = 255;
    const TYPE_TINYTEXT = 255;
    const TYPE_TEXT = 65535;
    const TYPE_MEDIUMTEXT = 16777215;
    const TYPE_LONGTEXT = 4294967295;
    const TYPE_BOOLEAN = 1;
    const TYPE_BIT = 1;
    const TYPE_DATETIME_ISO8601 = 19;
    const TYPE_DATETIME_ISO8601_PRECISION_A = 20;
    const TYPE_DATE_ISO8601 = 10;
    const TYPE_DATE_ISO8601_PRECISION_A = 11;  
    const TYPE_NULL = 0;
    
    
    const LENGTH_TINYINT = 127;
    const LENGTH_SMALLINT = 32767;
    const LENGTH_MEDIUMINT = 8388607;
    const LENGTH_INT = 2147483647;
    const LENGTH_BIGINT = 9223372036854775807;
    const LENGTH_FLOAT = 2147483647.9999999;
    const LENGTH_DOUBLE = 9223372036854775807.9999999999999999;
    const LENGTH_CHAR = 255;
    const LENGTH_VARCHAR = 255;
    const LENGTH_TINYTEXT = 255;
    const LENGTH_TEXT = 65535;
    const LENGTH_MEDIUMTEXT = 16777215;
    const LENGTH_LONGTEXT = 4294967295;
    const LENGTH_BOOLEAN = 1;
    const LENGTH_BIT = 1;
    const LENGTH_DATETIME_ISO8601 = 19;
    const LENGTH_DATETIME_ISO8601_PRECISION_A = 20;
    const LENGTH_DATE_ISO8601 = 10;
    const LENGTH_DATE_ISO8601_PRECISION_A = 11;  
    const LENGTH_NULL = 0;    
    
    const PARAM = 'param';
    const VALUE = 'value';
    const TYPE = 'type';
    const LENGTH = 'length';
    const QTD_PARAMS = 'qtdParams';  
    const MODIFY_FLAG_PARAM = 'modifyFlagParam';
    
    private $SQLDataTypes = Array
    (
        self::INT,
        self::VARCHAR,
        self::DATE_ISO8601,
        self::TIMESTAMP,
        self::DATETIME,
        self::VARCHAR_NO_QUOTES,
        self::DOUBLE,
        self::FLOAT,
        self::TIME,
        self::TEXT,
        self::BOOLEAN,
        self::BIT,       
    ); 
    
    public static function get($key)
    {
        return new CommandEnum($key);
    }  
    
    public function isSupportedType($type)
    {
        return in_array($type,$this->SQLDataTypes);
    }    
}
