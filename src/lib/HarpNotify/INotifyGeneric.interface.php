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
namespace etc\HarpNotify;

interface NotifyEnumGeneric
{       
    const NOTIFY_NAME = 'NOTIFY_NAME';
    const NOTIFY_TITLE = 'NOTIFY_TITLE';
    const NOTIFY_MESSAGE = 'NOTIFY_MESSAGE';
    const NOTIFY_MESSAGE_BREAK_LINES = 'NOTIFY_MESSAGE_BREAK_LINES';
    const NOTIFY_DEGENERATE_MESSAGE = 'NOTIFY_DEGENERATE_MESSAGE';
    const NOTIFY_ID = 'NOTIFY_ID'; 
    const NOTIFY_CLASS = 'NOTIFY_CLASS';
    const NOTIFY_VISIBLE = 'NOTIFY_VISIBLE';
    
    const NOTIFY_CLASS_DEFAULT = 'hidden';
    
    const MESSAGE_IN_RIGHT = 'r';
    const MESSAGE_IN_LEFT = 'l';
    const CLEAR_MESSAGE_TRUE = true;
    const CLEAR_MESSAGE_FALSE = false;    
}