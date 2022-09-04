/**
 * Copyright (c) 2015-2017 Leonardo Souza
 * Licensed under the MIT license
 * allezo.lss@gmail.com
 **/
(function ($) {
    $.fn.HarpFilter = function (options) 
    {
        var defaults = {
            'baseUrl': '',
            'stylesheet': 'theme-blue-gray',
            'fieldFilterName': 'hiddenFieldFilter',
            'jsonFilterData': '',
            'allowedRelationalOperators': {},
            'useQuotesInCommand': 1,
            'scapeQuotes': 1,
            'filterFieldWidth': '260px',
            'logicalOperatorWidth': '100px',
            'relationalOperatorWidth': '220px',
            'logicalOperatorMinWidth':'8rem',
            'logicalOperatorMaxWidth':'12rem',
            'filterFieldMinWidth':'15rem',
            'filterFieldMaxWidth':'30rem',
            'btnAddFilter': false,
            'btnCleanAllFilters':true,
            'defaultSearchShow': true,
            'defaultEnterKey': 'buttonSubmitFilter',
            'enableDefaultSearchOnChangeFilterField':true,
            'relationalOperatorFontSize':'1rem',
            'logicalOperatorFontSize':'1rem',
            'filterFieldFontSize':'1rem',
            'loadWhileSubmit':
            {
                'show': 1,
                'elementImage': '.showLoaderImage',
                'color':'#205081'
            }
        };

        var defaultSettings = defaults;

        var settings = $.extend({}, defaults, options);

        jQuery('head').append('<script type="text/javascript" src="' + settings.baseUrl + '/HarpFilter/js/fingerprintjs2/fingerprint2.js"></script>');
     //   jQuery('head').append('<link rel="stylesheet" type="text/css" href="' + settings.baseUrl + '/HarpFilter/css/HarpFilterDefault.css">');
        jQuery('head').append('<link rel="stylesheet" type="text/css" href="' + settings.baseUrl + '/HarpFilter/css/' + settings.stylesheet + '.css">');
        jQuery('head').append('<script type="text/javascript" src="' + settings.baseUrl + '/HarpFilter/js/jQuery-MD5/jquery.md5.js"></script>');
        jQuery('head').append('<script type="text/javascript" src="' + settings.baseUrl + '/HarpFilter/js/jquery-base64/jquery.base64.min.js"></script>');
        if (!window.jQuery) {
            jQuery('head').append('<script type="text/javascript" src="' + settings.baseUrl + '/HarpFilter/js/jquery-1.11.3.min.js"></script>');
        }
        if (typeof jQuery.ui == 'undefined') 
        {
            jQuery('head').append('<link rel="stylesheet" type="text/css" href="' + settings.baseUrl + '/HarpFilter/css/jquery-ui.min.css">');
            jQuery('head').append('<script type="text/javascript" src="' + settings.baseUrl + '/HarpFilter/js/jquery-ui.min.js"></script>');
        }
        
        jQuery('head').append('<script type="text/javascript" src="' + settings.baseUrl + '/HarpFilter/js/spin.js"></script>');
        jQuery('head').append('<script type="text/javascript" src="' + settings.baseUrl + '/HarpFilter/js/spinjQuery.js"></script>');
        if (jQuery.type(settings.loadWhileSubmit) == 'string') {
            settings.loadWhileSubmit = JSON.parse(settings.loadWhileSubmit);
        }
        jQuery.each(defaultSettings.loadWhileSubmit,function(r,t)
        {   
            if(typeof settings.loadWhileSubmit[r] == 'undefined')
            {
               settings.loadWhileSubmit[r] = t; 
            }
        });
       
        function loaderGif(elementBindClick) 
        {
            if (jQuery(settings.loadWhileSubmit.elementImage).length > 0) 
            {

                jQuery(settings.loadWhileSubmit.elementImage).spin
                (
                        {
                              lines: 13 // The number of lines to draw
                            , length: 28 // The length of each line
                            , width: 14 // The line thickness
                            , radius: 42 // The radius of the inner circle
                            , scale: 0.2 // Scales overall size of the spinner
                            , corners: 1 // Corner roundness (0..1)
                            , color: settings.loadWhileSubmit.color // #rgb or #rrggbb or array of colors
                            , opacity: 0.25 // Opacity of the lines
                            , rotate: 0 // The rotation offset
                            , direction: 1 // 1: clockwise, -1: counterclockwise
                            , speed: 1 // Rounds per second
                            , trail: 60 // Afterglow percentage
                            , fps: 20 // Frames per second when using setTimeout() as a fallback for CSS
                            , zIndex: 2e9           // Use a high z-index by default
                            , className: 'spinner'  // CSS class to assign to the element
                            , top: '50%'            // center vertically
                            , left: '50%'           // center horizontally
                            , shadow: false         // Whether to render a shadow
                            , hwaccel: false        // Whether to use hardware acceleration (might be buggy)
                            , position: 'absolute'  // Element positioning
                        }
                );                        
            }
        } 
                
        var idElement = jQuery(this).attr('id');



        if (typeof idElement === 'undefined' || idElement.trim() === '') {
            console.log('id element is required!');

            return false;
        }

        settings.defaultEnterKey = '#' + idElement + ' > .'+settings.defaultEnterKey;

        jQuery('#' + idElement).append('<input type="hidden" name="' + idElement + '-fingerPrint">');

        var fingerPrint = new Fingerprint2();

        fingerPrint.get(function (result, components) {
            console.log('result' + result);

            jQuery('input[name="' + idElement + '-fingerPrint"]').val(result);
        });

    //    var currentLocationHref = window.location.href;

        var currentFilter = jQuery.md5(window.location.href + idElement + jQuery('input[name="' + idElement + '-fingerPrint"]').val());
       //localStorage.removeItem(currentFilter);
        //  console.log(currentFilter);

        var sendRequest = false;

        if (settings.jsonFilterData.length === 0 || jQuery.isEmptyObject(settings.jsonFilterData)) {

            var obj = { filter: {}, parameters: {}, commandText: '', commandParameter: '', order: {}, logicalOperator: '', filterField: '', relationalOperator: '', request: '0' };
            var emptyFilter = jQuery.base64.encode(JSON.stringify(obj));

            if (typeof currentFilter != 'undefined' && currentFilter != '')
            {
                if (typeof (Storage) != "undefined")
                {
                    if (localStorage.getItem(currentFilter) != null) {
                        
                        var d = getJson(jQuery.base64.decode(localStorage.getItem(currentFilter)));

                        if (!jQuery.isEmptyObject(d.filter))
                        {
                            sendRequest = true;
                        }

                        settings.jsonFilterData = localStorage.getItem(currentFilter);
                    }
                    else
                    {

                        settings.jsonFilterData = emptyFilter;
                    }
                }
                else
                {
                    console.log('Sorry! No Web Storage support..');
                }
            }
            else {
                settings.jsonFilterData = emptyFilter;
            }
        }

        var typeofRelOperation = typeof settings.allowedRelationalOperators;

        if (typeofRelOperation != 'object') {
            var j = getJson(settings.allowedRelationalOperators);

            if (!j) {
                console.log('{allowedRelationalOperators} it should be an object or a string json. found: ' + typeofRelOperation);

                return false;
            }

            settings.allowedRelationalOperators = j;
        }

        jQuery('#' + idElement).css({'display':'inline-block','position':'relative','width':'100%'});
       // jQuery('#' + idElement).css({ 'display': 'block', 'float': 'left', 'margin': '0.5rem 0 0.5rem 0' });

        


        var isMobile =  /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        var idContainer = idElement + 'Container';
        
        jQuery('#' + idElement).append('<div class="ContainerElement"></div>');
     
        if(!isMobile)
        {
            jQuery('#' + idElement).append('<button class="buttonAddFilter"><img src="' + settings.baseUrl + '/HarpFilter/img/add48.png"/></button>');
            jQuery('#' + idElement).append('<button class="buttonSubmitFilter"><img src="' + settings.baseUrl + '/HarpFilter/img/MagnifyingGlass48.png"/></button>');

            if(settings.btnCleanAllFilters)
            {
                jQuery('#' + idElement).append('<button class="cleanAllFilter"><img src="' + settings.baseUrl + '/HarpFilter/img/clean.png"/></button>');
            }
            
            jQuery('.filterField').children().css({ 'min-width': settings.filterFieldMinWidth,'max-width':settings.filterFieldMaxWidth,'font-size':settings.filterFieldFontSize});  
            jQuery('.logicalOperator').children().css({'min-width':settings.logicalOperatorMinWidth,'max-width':settings.logicalOperatorMaxWidth,'font-size':settings.logicalOperatorFontSize});
            jQuery('.relationalOperator').children().css({ 'min-width': settings.relationalOperatorMinWidth,'max-width': settings.relationalOperatorMaxWidth,'font-size':settings.relationalOperatorFontSize});

        }   
        else
        {
            jQuery('#' + idElement).append('<button class="buttonAddFilter" style="color:#FFFFFF;">&#43;</button>');
            jQuery('#' + idElement).append('<button class="buttonSubmitFilter" style="color:#FFFFFF;">&#8634;</button>');

            if(settings.btnCleanAllFilters)
            {
                jQuery('#' + idElement).append('<button class="cleanAllFilter" style="color:#FFFFFF;">&#128465;</button>');
            }  
        }

        jQuery('#' + idElement).append('<span class="showLoaderImage" style="float:right;display:inline-block;width:60px;height:60px;border:1px solid transparent;"></span>');
        
        jQuery('#' + idElement).append('<div class="ActiveFilters"></div>');
        jQuery('#' + idContainer).hide();
        jQuery('#' + idElement + ' > .ContainerElement').hide();
        jQuery('#' + idElement + ' > .buttonAddFilter').hide();
        jQuery('#' + idElement + ' > .buttonSubmitFilter').hide();
        jQuery('#' + idElement + ' > .cleanAllFilter').hide();
        jQuery('#' + idElement + ' > .ActiveFilters').hide();
      //  console.log(settings.filterFieldMinWidth);

        if (jQuery('#' + idElement + ' > .filterData').length === 0) {
            jQuery('#' + idElement).append('<input class="filterData" type="hidden" name="' + settings.fieldFilterName + '" value="' + settings.jsonFilterData + '">');
        }

        if (jQuery('#' + idContainer).is(':empty')) {
            console.log('There are no elements to perform the filter!');

            return false;
        }
        else if (jQuery('#' + idElement).find('.logicalOperator > select').length === 0) {
            console.log('Logical operators have not been added to the filter!');

            return false;
        }
        else if (jQuery('#' + idElement).find('.filterField > select').length === 0) {
            console.log('Filter fields have not been added to the filter!');

            return false;
        }
        else if (jQuery('#' + idElement).find('.relationalOperator > select').length === 0) {
            console.log('Relational operators have not been added to the filter!');

            return false;
        }

        jQuery('#' + idElement + ' > .relationalOperator > select').prop('disabled', true);

        jQuery('#' + idContainer).children().each(function (e, r) {
            jQuery(r).prop('disabled', true);
        });
    
        function validateUrl(textval)   // return true or false.
        {
            var regex = /^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/;

            var urlregex = new RegExp(regex);
            return urlregex.test(textval);
        }

        var elementAdrress = '';

        jQuery('body').on('click', 'button,a,input[type="submit"]', function (e) {
            if (jQuery(this).attr('class') != 'buttonSubmitFilter') {
                var nodeName = this.nodeName;
               
                var testUri = false;

                if (nodeName == 'A')
                {
                    elementAdrress = jQuery(this).attr('href');
                }
                else
                {
                    elementAdrress = jQuery(this).closest('form').attr('action');

                    testUri = (elementAdrress == '#' || elementAdrress.trim() == '') ? false : true;
                }

                elementAdrress = (typeof elementAdrress == 'undefined') ? '' : elementAdrress;
                
                var lastChar = elementAdrress.substr(elementAdrress.length - 1);

                if (lastChar == '#')
                {
                    elementAdrress = elementAdrress.substring(0, elementAdrress.length - 1);
                }

                //hack for aspx trash pages
                //para páginas aspx lixo
                substring = "javascript:WebForm_DoPostBackWithOptions";

                if (elementAdrress.indexOf(substring) != -1) {
                    var regExp = /\(([^)]+)\)/;
                    var matches = regExp.exec(elementAdrress);

                    if (matches != null && matches.length > 0) {
                        matches = regExp.exec(matches[0]);

                        if (matches != null && matches.length > 0) {
                            var parts = matches[1].split(',');

                            if (parts != null && typeof parts[4] != 'undefined') {
                                elementAdrress = parts[4];
                                testUri = true;
                            }
                        }

                    }
                }

                if (validateUrl(elementAdrress) || testUri === true) {
                    //if (currentLocationHref != elementAdrress) 
                    // {
                    if (typeof (Storage) != "undefined") {
                        var cacheFilter = localStorage.getItem(currentFilter);

                        if (cacheFilter != null) {
                            var cache = getJson(jQuery.base64.decode(cacheFilter));

                            if (!jQuery.isEmptyObject(cache.filter)) {
                                cache.request = 1;

                                jQuery('#' + idElement + ' > .filterData').val(jQuery.base64.encode(JSON.stringify(cache)));

                                localStorage.setItem(currentFilter, jQuery('#' + idElement + ' > .filterData').val());
                            }
                        }

                    }
                    //  }
                }
            }

        });

        if (typeof (Storage) != "undefined") {
            var cacheFilter = localStorage.getItem(currentFilter);

            if (cacheFilter != null) {
                cacheFilter = getJson(jQuery.base64.decode(cacheFilter));

                if (!jQuery.isEmptyObject(cacheFilter.filter) && cacheFilter.request == 1) {
                    cacheFilter.request = '0';

                    jQuery('#' + idElement + ' > .filterData').val(jQuery.base64.encode(JSON.stringify(cacheFilter)));

                    localStorage.setItem(currentFilter, jQuery('#' + idElement + ' > .filterData').val());
                    loaderGif('#' + idElement + ' > .buttonSubmitFilter'); 
                    jQuery('#' + idElement + ' > .buttonSubmitFilter').trigger('click');
                }
            }
        }

        jQuery('body').on('click', '#' + idElement + ' .cleanAllFilter', function (e) {
            jQuery('#' + idElement + ' .ActiveFilters').empty();

            var jsonObject = { filter: {}, parameters: {}, commandText: '', commandParameter: '', order: {}, logicalOperator: '', filterField: '', relationalOperator: '', request: '0' };

            jQuery('#' + idElement + ' > .filterData').val(jQuery.base64.encode(JSON.stringify(jsonObject)));

            if (typeof (Storage) != "undefined") {
                localStorage.setItem(currentFilter, jQuery('#' + idElement + ' > .filterData').val());
            }

            if (settings.loadWhileSubmit.show) {
                loaderGif('#' + idElement + ' .cleanAllFilter');
            }

        });
        
        jQuery('.relationalOperator').show();
        jQuery('.logicalOperator').show();
        jQuery('.filterField').show();

        var logicalOperatorValue = jQuery('#' + idElement + ' > .logicalOperator > select').val();
        var relationalOperatorValue = jQuery('#' + idElement + ' > .relationalOperator > select').val();
        var filterFieldValue = '';
        var filterFieldValue2 = {};
        var filterFieldClass = '';
        var filterElementSelected = null;
        var filterElementSelected2 = null;
        var strCommand = null;
        var strDisplay = null;
        var keyParam = '';
        var keyParam2 = '';
        var strCommandParameter = '';
        var param = '';
        var param2 = '';
        var type = null;
        var position = 'center';
        var valueSelected = null;
        var valueSelected2 = null;
        var formatedElement = null;
        var formatedElement2 = null;
        var secondColumn = null;

        var length =
        {
            'tinyint': 127,
            'smallint': 32767,
            'int': 2147483647,
            'bigint': 9223372036854775807,
            'varchar': 255,
            'nvarchar': 255,
            'nchar': 255,
            'char': 255,
            'text': 65535,
            'ntext': 65535,
            'tinytext': 255,
            'mediumtext': 16777215,
            'longtext': 4294967295,
            'boolean': 1,
            'bit': 1,
            'datetime': 19,
            'date': 10,
            'float': 2147483647.9999999,
            'decimal': 9223372036854775807.9999999999999999,
            'double': 9223372036854775807.9999999999999999,
            'real': 9223372036854775807.9999999999999999,
        };

        function guid() {
            var str = new Date().toJSON();

            for (i = 0; i <= 8; ++i) {
                str += Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
            }

            return str;
        }

        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
              .toString(16)
              .substring(1);
        }

        function validateDate(date) {
            var f = ['/', '-', '.'];

            var simbol = '';

            jQuery.each(f, function (e, r) {
                if (date.indexOf(r) > -1) {
                    simbol = r;

                    return false;
                }
            });

            var a = date.split(simbol);

            if (a.length === 3) {
                var dd = parseInt(a[0]);
                var mm = parseInt(a[1]);
                var yyyy = parseInt(a[2]);
                var listOfDays = [28, 29, 30, 31];
                var daysOfMonth = new Date(yyyy, mm, 0).getDate();

                if ((jQuery.inArray(daysOfMonth, listOfDays) === -1) || (mm < 1 || mm > 12) || ((mm === 2 || mm === 02) && dd > daysOfMonth)) {
                    return false;
                }

                return true;
            }

            return false;
        }

        function addslashes(str) {
            return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
        }


        function isNumeric(valor) {
            var v = valor.replace(',', '').replace('.', '');

            return jQuery.isNumeric(v);
        }

        function isInteger(valor) {
            if (jQuery.isNumeric(valor) && Math.floor(valor) === parseFloat((valor).trim())) {
                return true;
            }

            return false;
        }

        function isBit(valor) {
            if (valor.trim().length === 0 || valor.trim().length > 1 || !isInteger(valor)) {
                return false;
            }

            return true;
        }

        function isNullOrEmpty(valor) {
            return valor.trim().length > 0;
        }

        function getJson(str) {
            var s = null;

            try {
                s = JSON.parse(str);
            }
            catch (e) {
                return false;
            }

            return s;
        }
        
        function daysInMonth(m, y) {
            m = m - 1;

            switch (m) {
                case 1:
                    return (y % 4 == 0 && y % 100) || y % 400 == 0 ? 29 : 28;
                case 8: case 3: case 5: case 10:
                    return 30;
                default:
                    return 31;
            }
        }

        function formatDate(val, dataFormat) {
            var v = val.trim().split('/');

            var newDate = val;

            if (v.length == 3) {
                newDate = v[2] + '-' + v[1] + '-' + v[0];
            }
            else {
                v = val.trim().split('.');

                if (v.length == 3) {
                    newDate = v[2] + '-' + v[1] + '-' + v[0];
                }
                else {
                    v = val.trim().split(' ');

                    if (v.length == 3) {
                        newDate = v[2] + '-' + v[1] + '-' + v[0];
                    }
                    else {
                        v = val.trim().split('-');

                        if (v.length == 3) {
                            newDate = v[2] + '-' + v[1] + '-' + v[0];
                        }
                    }
                }
            }

            if (newDate != null) {
                val = jQuery.datepicker.formatDate(dataFormat, new Date(parseInt(v[2]), parseInt(v[1]) - 1, parseInt(v[0])));
            }

            return val;
        }

        function formatMoney(n, c, d, t) {
            n = n.replace(/\./g, '');
            n = n.replace(/\,/g, '.');
            n = parseFloat(n).toFixed(c);

            return n;
        };

        function validateType(type, val, element) {
            var status = false;

            switch (type) {
                case 'varchar':
                case 'nvarchar':
                case 'nchar':
                case 'char':
                case 'text':
                case 'tinytext':
                case 'mediumtext':
                case 'longtext':
                case 'ntext':
                    status = isNullOrEmpty(val);
                    if (status) { return val; }
                    break;
                case 'int':
                case 'smallint':
                case 'tinyint':
                case 'bigint':
                    status = isInteger(val);
                    if (status) { return val; }
                    break;
                case 'bit':
                    status = isBit(val);
                    if (status) { return val; }
                    break;
                case 'date':
                    status = validateDate(val);
                    if (status) {
                        if (element != null) {
                            var dataFormat = jQuery(element).attr('data-format');

                            if (dataFormat != 'undefined' && dataFormat != '') {
                                val = formatDate(val, dataFormat);
                            }

                        }

                        return val;
                    }
                case 'datetime':
                    status = validateDate(val);

                    if (status) {
                        if (element != null) 
                        {
                            var dataFormat = jQuery(element).attr('data-format');
                            var dataTimeStart = jQuery(element).attr('data-time');

                            dataTimeStart = (typeof dataTimeStart == 'undefined' || dataTimeStart.trim() == '') ? '00:00:00' : dataTimeStart;
                            
                            if (typeof dataFormat != 'undefined' && dataFormat != '') {
                                val = formatDate(val, dataFormat);
                            }

                        }

                        val += ' ' + dataTimeStart;

                        return val;
                    }
                    break;
                case 'float':
                case 'decimal':
                case 'double':
                case 'real':
                    status = isNumeric(val);

                    if (status) {
                        val = formatMoney(val, 2);

                        return val;
                    }
                    break;
                default:
                    console.log('Impossible to validate the value because the type: ' + type + ' is not suported!');
                    status = false;
                    break;
            }

            return status;

        }

        function validateFilterTypeValue() 
        {

            if (filterFieldClass.trim().length === 0) {
                console.log('Impossible to validate the value, it seems that this element value is null!');

                return false;
            }
            else if (typeof jQuery('.' + filterFieldClass).attr('type') === 'undefined' || jQuery('.' + filterFieldClass).attr('type').trim().length === 0) {
                console.log('attribute type Not Set,Is impossible to validate the value!');

                return false;
            }

            type = jQuery('.' + filterFieldClass).attr('type');
            
            formatedElement = jQuery(filterElementSelected).val();
            //   console.log(formatedElement);
            var status = validateType(type, formatedElement, filterElementSelected);

            var typeArray = ['datetime', 'date', 'float', 'decimal', 'double', 'real'];

            if (jQuery.inArray(type, typeArray) != -1) { formatedElement = status; }

            if (status === false) { var msg = jQuery(filterElementSelected).attr('data-msg'); }

            if (status && filterElementSelected2 != null && jQuery('#' + idElement + ' > .ContainerElement').children().size() == 2) {
                formatedElement2 = jQuery(filterElementSelected2).val();

                var status = validateType(type, formatedElement2, filterElementSelected2);

                if (jQuery.inArray(type, typeArray) != -1) { formatedElement2 = status; }

                if (!status) { var msg = jQuery(filterElementSelected2).attr('data-msg'); }
            }
            // console.log(formatedElement,formatedElement2);return false;
            if (status === false) {
                alert(msg);

                return false;
            }
            else if (jQuery(filterElementSelected).val().trim().length > length[type]) {
                alert(msg + ' type:' + type + ' size allowed: ' + length[type]);

                return false;
            }
            else if (jQuery(filterElementSelected2).length > 0 && jQuery(filterElementSelected2).val().trim().length > length[type]) {
                alert(msg + ' type:' + type + ' size allowed: ' + length[type]);

                return false;
            }
            var relationalOperatorDisplay = jQuery('#' + idElement + ' > .relationalOperator > select').find(":selected").text();
            var logicalOperatorDisplay = jQuery('#' + idElement + ' > .logicalOperator > select').find(":selected").text();
            var filterFieldDisplay = jQuery('#' + idElement + ' > .filterField > select').find(":selected").text();

            var tickets = getJson(jQuery.base64.decode(jQuery('#' + idElement + ' > .filterData').val()));

            var typeElement = jQuery(filterElementSelected).prop('type');

            var typeString = ['varchar', 'nvarchar', 'nchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'ntext'];

            //                valueSelected = (jQuery.inArray(type,typeString) != -1 && settings.scapeQuotes == 1) ? addslashes(jQuery(filterElementSelected).val()) : jQuery(filterElementSelected).val();
            valueSelected = (jQuery.inArray(type, typeString) != -1 && settings.scapeQuotes == 1) ? addslashes(formatedElement) : formatedElement;
            // var pDisplay = (typeElement != 'select-multiple' && typeElement != 'select-one') ? jQuery(filterElementSelected).val() : jQuery(filterElementSelected).find(':selected').text();
            var pDisplay = (typeElement != 'select-multiple' && typeElement != 'select-one') ? formatedElement : jQuery(filterElementSelected).find(':selected').text();
            var pDisplay2 = null;
            
            if (jQuery(filterElementSelected2).length > 0) 
            {
                valueSelected2 = (jQuery.inArray(type, typeString) != -1 && settings.scapeQuotes == 1) ? addslashes(formatedElement2) : formatedElement2;
                
                pDisplay2 = (typeElement != 'select-multiple' && typeElement != 'select-one') ? formatedElement2 : jQuery(filterElementSelected2).find(':selected').text();
                    
                secondColumn = jQuery('#' + idElement + ' > .filterField > select').find(":selected").attr('data-second-column');
               
                if(typeof secondColumn == 'undefined' && relationalOperatorValue.toUpperCase() == 'BETWEEN-TWO-DIFFERENT-COLUMNS')
                {
                    secondColumn = null;

                    console.log('Attribute data-second-column is missing!');

                    return false;
                }
               
                if(secondColumn != null){ secondColumn = secondColumn.toUpperCase();}
            }
            //console.log(valueSelected2);return false;
            if (type === 'int' || type === 'bit') 
            {
                strCommand = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' ' + relationalOperatorValue).toUpperCase() + ' ' + formatedElement + ' ';

                strDisplay = ((!jQuery.isEmptyObject(tickets.filter) ? logicalOperatorDisplay : '') + ' ' + filterFieldDisplay + ' ' + relationalOperatorDisplay).toUpperCase() + ' ' + pDisplay;
                fullStrDisplay = (logicalOperatorDisplay + ' ' + filterFieldDisplay + ' ' + relationalOperatorDisplay).toUpperCase() + ' ' + pDisplay;
                //Garantir que a chave é única para um comando
                keyParam = '@p' + jQuery.md5(fullStrDisplay);

                param = keyParam;

                if (relationalOperatorValue == 'BETWEEN' || relationalOperatorValue == 'between') 
                {
                    strCommand = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' ' + relationalOperatorValue).toUpperCase() + ' ' + formatedElement + (formatedElement2 == null ? ' ' : ' AND ' + formatedElement2);
                    fullStrDisplay = (logicalOperatorDisplay + ' ' + filterFieldDisplay + ' ' + relationalOperatorDisplay).toUpperCase() + ' ' + pDisplay + (pDisplay2 != null ? ' & ' + pDisplay2 : '');
                    strDisplay = ((!jQuery.isEmptyObject(tickets.filter) ? logicalOperatorDisplay : '') + ' ' + filterFieldDisplay + ' ' + relationalOperatorDisplay).toUpperCase() + ' ' + pDisplay + (pDisplay2 != null ? ' & ' + pDisplay2 : '');

                    keyParam2 = '@p2' + keyParam.substring(2) + '-1';

                    param2 = keyParam2;
                    pCommand2 = valueSelected2;
                }
                else if(relationalOperatorValue.toUpperCase() == 'BETWEEN-TWO-DIFFERENT-COLUMNS')
                {                    
                    strCommand = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' >= ').toUpperCase() + ' ' + formatedElement + (formatedElement2 == null ? ' ' : ' AND ' + secondColumn + ' <= ' +formatedElement2);
                    
                    fullStrDisplay = (logicalOperatorDisplay + ' ' + filterFieldDisplay + ' >= ').toUpperCase() + ' ' + pDisplay + (pDisplay2 != null ? ' & <= ' + pDisplay2 : '');

                    strDisplay = ((!jQuery.isEmptyObject(tickets.filter) ? logicalOperatorDisplay : '') + ' ' + filterFieldDisplay + ' >= ').toUpperCase() + pDisplay + (pDisplay2 != null ? ' & <= '+ pDisplay2 : '');

                    keyParam2 = '@p2' + keyParam.substring(2) + '-1';

                    param2 = keyParam2;
                    
                    param2 = "'"+keyParam2+"'";
                    
                    pCommand2 = valueSelected2;                  
                }
            }
            else 
            {

                var wildcard = ['%', '%'];

                if (position == 'left') {
                    wildcard = ['', '%'];
                }
                else if (position == 'right') {
                    wildcard = ['%', ''];
                }

                var pCommandDisplay = (relationalOperatorValue === 'LIKE' || relationalOperatorValue === 'like') ? " '" + wildcard[0] + pDisplay + wildcard[1] + "' " : " '" + pDisplay + "' " + (pDisplay2 != null ? "& '" + pDisplay2 + "'" : '');

                strDisplay = ((!jQuery.isEmptyObject(tickets.filter) ? logicalOperatorDisplay : '') + ' ' + filterFieldDisplay + ' ' + relationalOperatorDisplay).toUpperCase() + pCommandDisplay;

                var fullStrDisplay = (logicalOperatorDisplay + ' ' + filterFieldDisplay + ' ' + relationalOperatorDisplay).toUpperCase() + pCommandDisplay;
                //Garantir que a chave é única para um comando
                keyParam = '@p' + jQuery.md5(fullStrDisplay);

                if (pDisplay2 != null) {
                    keyParam2 = '@p2' + keyParam.substring(2) + '-1';
                }

                if (settings.useQuotesInCommand == 1) {
                    var pCommand = (relationalOperatorValue === 'LIKE' || relationalOperatorValue === 'like') ? "'" + wildcard[0] + valueSelected + wildcard[1] + "'" : "'" + valueSelected + "'";
                    var pCommand2 = null;
                    param = (relationalOperatorValue === 'LIKE' || relationalOperatorValue === 'like') ? "'" + wildcard[0] + keyParam + wildcard[1] + "'" : "'" + keyParam + "'";


                    if (valueSelected2 != null) {
                        pCommand2 = (relationalOperatorValue === 'LIKE' || relationalOperatorValue === 'like') ? "'" + wildcard[0] + valueSelected + wildcard[1] + "'" : "'" + valueSelected2 + "'";
                        param2 = (relationalOperatorValue === 'LIKE' || relationalOperatorValue === 'like') ? "'" + wildcard[0] + keyParam + wildcard[1] + "'" : "'" + keyParam2 + "'";
                    }

                }
                else 
                {
                    var pCommand = (relationalOperatorValue === 'LIKE' || relationalOperatorValue === 'like') ? wildcard[0] + valueSelected + wildcard[1] : valueSelected;

                    param = (relationalOperatorValue === 'LIKE' || relationalOperatorValue === 'like') ? wildcard[0] + keyParam + wildcard[1] : keyParam;
                }

                if (relationalOperatorValue == 'BETWEEN' || relationalOperatorValue == 'between') 
                {
                    strCommand = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' ' + relationalOperatorValue).toUpperCase() + ' ' + pCommand + ' ' + (pCommand2 != null ? 'AND ' + pCommand2 : '');
                }
                else if(relationalOperatorValue.toUpperCase() == 'BETWEEN-TWO-DIFFERENT-COLUMNS')
                {
                    strCommand = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' >= ').toUpperCase() + ' ' + formatedElement + (formatedElement2 == null ? ' ' : ' AND ' + secondColumn + ' <= ' +formatedElement2);
                    //console.log(strCommand);return false;
                    fullStrDisplay = (logicalOperatorDisplay + ' ' + filterFieldDisplay + ' >= ').toUpperCase() + ' ' + pDisplay + (pDisplay2 != null ? ' & <= ' + pDisplay2 : '');

                    strDisplay = ((!jQuery.isEmptyObject(tickets.filter) ? logicalOperatorDisplay : '') + ' ' + filterFieldDisplay + ' >= ').toUpperCase() + pDisplay + (pDisplay2 != null ? ' & <= '+ pDisplay2 : '');

                    keyParam2 = '@p2' + keyParam.substring(2) + '-1';

                    param2 = "'"+keyParam2+"'";
                   // console.log(param2);return false;
                    pCommand2 = valueSelected2;     
                    
                   // console.log(strCommand);return false;
                }                
                else 
                {
                    strCommand = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' ' + relationalOperatorValue).toUpperCase() + ' ' + pCommand;
                }             
            }

            if (relationalOperatorValue == 'BETWEEN' || relationalOperatorValue == 'between') 
            {
                strCommandParameter = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' ' + relationalOperatorValue).toUpperCase() + ' ' + param + ' ' + (pCommand2 != null ? 'AND ' + param2 : '');
            }
            else if(relationalOperatorValue.toUpperCase() == 'BETWEEN-TWO-DIFFERENT-COLUMNS')
            {
                strCommandParameter = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' >= ').toUpperCase() + param + ' ' + (pCommand2 != null ? 'AND ' +secondColumn+ ' <= ' + param2 : '');
                
                //console.log(strCommandParameter);return false;
            }             
            else 
            {
                strCommandParameter = (' ' + logicalOperatorValue + ' ' + filterFieldValue + ' ' + relationalOperatorValue).toUpperCase() + ' ' + param;
            }
            
            //console.log(strCommandParameter);return false;
            
            //console.log(strCommandParameter);return false;
            return true;
        }


        jQuery('html,body').on("keypress", function (e) 
        {
            e.stopPropagation();

            if (e.keyCode == 13) {
                var stats = false;

                if (!jQuery('#' + idElement + ' > .ContainerElement').is(':empty')) {
                    jQuery('#' + idElement + ' > .ContainerElement').each(function (x, t) {
                        if (jQuery(t).children().length > 0 && jQuery(t).children().css('display') != 'none') {
                            jQuery(t).children().focus();

                            stats = true;

                            return false;
                        }
                    });

                    if (stats) {
                        e.preventDefault();
                        //   jQuery('#' + idElement + ' > .buttonSubmitFilter').trigger('click');
                        jQuery(settings.defaultEnterKey).trigger('click');
                    }
                }
            }
        });

        return this.each(function () 
        {
//            jQuery('body').on('load',function()
//            {
//                jQuery('#' + idElement + ' > .logicalOperator').hide();
//                jQuery('#' + idElement + ' > .relationalOperator').hide();
//                jQuery('#' + idElement + ' > .filterField').hide();  
//            });
            if (jQuery.isEmptyObject(settings.jsonFilterData.filter)) {
                jQuery('#' + idElement + ' > .cleanAllFilter').hide();
                jQuery('#' + idElement + ' > .logicalOperator > select option').eq(1).prop('disabled', true);
            }

            var tickets = getJson(jQuery.base64.decode(settings.jsonFilterData));

            if (tickets) {
                if (typeof tickets.filter != 'undefined') {
                    var labels = '';

                    jQuery.each(tickets.filter, function (keyParam, r) {
                        if (r.doubleParameter == 0 || r.doubleParameter == 1) {
                            labels += '<span class="label-ticket"><div>' + r.strDisplay + '</div><button value="' + keyParam + '" data-value="' + r.value + '"><img src="' + settings.baseUrl + '/HarpFilter/img/x.png" width="16px"></button></span>';
                        }

                    });

                    if (labels.trim().length > 0) {
                        jQuery('#' + idElement + ' > .ActiveFilters').append(labels).show();
                        jQuery('#' + idElement + ' > .logicalOperator > select option').eq(1).prop('disabled', false);
                        jQuery('#' + idElement + ' > .buttonSubmitFilter').show();
                        if(settings.btnCleanAllFilters)
                        {
                            jQuery('#' + idElement + ' > .cleanAllFilter').show();
                        }
                        
                    }
                }

                var lo = (typeof tickets.logicalOperator != 'undefined');
                var ro = (typeof tickets.relationalOperator != 'undefined');
                var ff = (typeof tickets.filterField != 'undefined');

                if (lo && ro && ff && tickets.logicalOperator != '' && tickets.relationalOperator != '' && tickets.filterField != '') {
                    jQuery('select[name="' + idElement + '-logicalOperator"]').val(tickets.logicalOperator);
                    jQuery('select[name="' + idElement + '-RelationalOperator"]').val(tickets.relationalOperator);
                    jQuery('select[name="' + idElement + '-filterField"]').val(tickets.filterField);

                    var idElementCurrent = jQuery('select[name="' + idElement + '-filterField"]').find('option:selected').attr('class');

                    if (typeof idElementCurrent != 'undefined') {
                        var cln = null;
                        var cln1 = null;

                        if (jQuery('input[id^="' + idElementCurrent + '-"]').length > 0) {
                            cln = jQuery('input[id^="' + idElementCurrent + '-"]').eq(0).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + idElementCurrent + '-1');
                            cln1 = jQuery('input[id^="' + idElementCurrent + '-"]').eq(1).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + idElementCurrent + '-2');
                            jQuery('#' + idElement + ' > .ContainerElement').empty().append(cln).append(cln1).show();
                        }
                        else {
                            cln = jQuery("#" + idElementCurrent).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + idElementCurrent);
                            jQuery('#' + idElement + ' > .ContainerElement').empty().append(cln).show();
                        }


                        jQuery('select[name="' + idElement + '-RelationalOperator"]').prop('disabled', false);

                        filterElementSelected = cln;
                        filterElementSelected2 = cln1;
                        filterFieldClass = idElementCurrent;
                        filterFieldValue = tickets.filterField;
                        logicalOperatorValue = jQuery('select[name="' + idElement + '-logicalOperator"]').val();
                        position = 'center';

                        var attrPosition = jQuery('select[name="' + idElement + '-RelationalOperator"]').find('option:selected').attr('data-position');

                        if (!jQuery.isEmptyObject(settings.allowedRelationalOperators))
                        {
                            jQuery('#' + idElement + ' > .relationalOperator > select > option').each(function (e, r)
                            {
                                if (typeof settings.allowedRelationalOperators[filterFieldValue] !== 'undefined')
                                {
                                    var allowed = settings.allowedRelationalOperators[filterFieldValue];

                                    if(jQuery.inArray(jQuery(r).val().trim(), allowed) === -1)
                                    {
                                        jQuery(r).prop('disabled', true).css({ 'color': '#888888' });
                                    }
                                }
                            });
                        }
                        
                        relationalOperatorValue = jQuery('select[name="' + idElement + '-RelationalOperator"]').val();

                        if (typeof attrPosition != 'undefined') 
                        {
                            position = attrPosition;
                        }
                        
                        //console.log(filterFieldValue);
                    }

                }
                else 
                {
                    if (settings.defaultSearchShow)
                    {
                        defaultSearchShow(true);
                    }
                }

                if (sendRequest === true) {
                    jQuery('#' + idElement + ' > .buttonSubmitFilter').trigger('click');
                }

            }
            
            function defaultSearchShow(s)
            {
                        if(s)
                        {
                            jQuery('select[name="' + idElement + '-logicalOperator"] option').eq(0).prop('selected', true);

                         //   jQuery('select[name="' + idElement + '-RelationalOperator"] option').eq(1).prop('selected', true);
                            jQuery('select[name="' + idElement + '-filterField"] option').eq(1).prop('selected',true);

                        }

                        var idElementCurrent = jQuery('select[name="' + idElement + '-filterField"]').find('option:selected').attr('class');

                        if (typeof idElementCurrent != 'undefined') {
                            var cln = null;
                            var cln1 = null;

                            if (jQuery('input[id^="' + idElementCurrent + '-"]').length > 0) {
                                cln = jQuery('input[id^="' + idElementCurrent + '-"]').eq(0).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + idElementCurrent + '-1');
                                cln1 = jQuery('input[id^="' + idElementCurrent + '-"]').eq(1).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + idElementCurrent + '-2');
                                jQuery('#' + idElement + ' > .ContainerElement').empty().append(cln).append(cln1).show();
                            }
                            else {
                                cln = jQuery("#" + idElementCurrent).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + idElementCurrent);
                                jQuery('#' + idElement + ' > .ContainerElement').empty().append(cln).show();
                            }

                            jQuery('select[name="' + idElement + '-RelationalOperator"]').prop('disabled', false);

                            filterElementSelected = cln;
                            filterElementSelected2 = cln1;
                            filterFieldClass = idElementCurrent;
                            filterFieldValue = jQuery('select[name="' + idElement + '-filterField"]').val();
                           
                            logicalOperatorValue = jQuery('select[name="' + idElement + '-logicalOperator"]').val();
                            position = 'center';

                            var attrPosition = jQuery('select[name="' + idElement + '-RelationalOperator"]').find('option:selected').attr('data-position');

                            if (typeof attrPosition != 'undefined') {
                                position = attrPosition;
                            }
                            
                            if (!jQuery.isEmptyObject(settings.allowedRelationalOperators))
                            {
                                jQuery('#' + idElement + ' > .relationalOperator > select > option').each(function (e, r)
                                {
                                    if (typeof settings.allowedRelationalOperators[filterFieldValue] !== 'undefined')
                                    {
                                        var allowed = settings.allowedRelationalOperators[filterFieldValue];

                                        if(jQuery.inArray(jQuery(r).val().trim(), allowed) === -1)
                                        {
                                            jQuery(r).prop('disabled', true).css({ 'color': '#888888' });
                                        }
                                    }
                                });
                            }
                            
                            jQuery('select[name="' + idElement + '-RelationalOperator"] option').each(function(r,t)
                            {
                                if(jQuery(t).val().trim() != '' && jQuery(t).is(':enabled'))
                                {
                                    jQuery(t).prop('selected', true);

                                    return false;
                                }
                            });
                            
                            relationalOperatorValue = jQuery('select[name="' + idElement + '-RelationalOperator"]').val();

                            jQuery('#' + idElement + ' > .buttonSubmitFilter').show();
                        }
            }
            
            function onChangeFilterField(_this)
            {
                filterFieldValue = jQuery(_this).val();

                if (typeof jQuery('> option[value="' + filterFieldValue + '"]',_this).attr('class') === 'undefined' && filterFieldValue.length !== 0) {
                    console.log('class attribute has not been set!');

                    return false;
                }
                
                filterFieldClass = jQuery('> option[value="' + filterFieldValue + '"]',_this).attr('class');

                if (filterFieldValue.length === 0) 
                {
                    enabledRelationalOperators(filterFieldValue);

                    jQuery('#' + idElement + ' > .ContainerElement').empty().hide();

                    return false;
                }

                enabledRelationalOperators(filterFieldValue);

                if (jQuery("#" + filterFieldClass).length > 0) 
                {
                    filterElementSelected = jQuery("#" + filterFieldClass).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + filterFieldClass).hide();
                    jQuery('#' + idElement + ' > .ContainerElement').empty().append(filterElementSelected).hide();
                    
                    if(settings.enableDefaultSearchOnChangeFilterField)
                    {
                        jQuery(filterElementSelected).show();
                        jQuery('#' + idElement + ' > .ContainerElement').show();
                    }   
                }
                else if (jQuery('input[id^="' + filterFieldClass + '-"]').length > 0) 
                {
                    filterElementSelected = jQuery('input[id^="' + filterFieldClass + '-"]').eq(0).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + filterFieldClass + '-1').hide();
                    filterElementSelected2 = jQuery('input[id^="' + filterFieldClass + '-"]').eq(1).clone(true).prop('disabled', false).removeAttr('id').addClass('cloned-element').attr('id', 'cloned-element-' + filterFieldClass + '-2').hide();

                    jQuery('#' + idElement + ' > .ContainerElement').empty().append(filterElementSelected).append(filterElementSelected2).hide();
                    
                    if(settings.enableDefaultSearchOnChangeFilterField)
                    {
                        jQuery(filterElementSelected).show();
                        jQuery(filterElementSelected2).show();
                        jQuery('#' + idElement + ' > .ContainerElement').show();
                    }  
                }  
                
                if(settings.enableDefaultSearchOnChangeFilterField)
                {
                    enabledFirstRelationalOperator();
                }
               
            }
            
            function onChangeRelationalOperator(_this)
            {
                relationalOperatorValue = jQuery(_this).val().trim();
                //console.log(relationalOperatorValue);
                if (relationalOperatorValue.length === 0) {
                    console.log('Relational operator not found!');

                    return false;
                }

                var attrPosition = jQuery(_this).find(":selected").attr('data-position');

                if (typeof attrPosition != 'undefined') {
                    position = attrPosition;
                }
                console.log(jQuery(filterElementSelected));
                jQuery(filterElementSelected).show();

                if (filterElementSelected2 != null) 
                {
                    jQuery(filterElementSelected2).show();
                }
            //
                jQuery('#' + idElement + ' > .ContainerElement').css({ 'display': 'inline-block' });
              
                enabledButtons();
            }
            
            function enabledButtons()
            {
                if (settings.btnAddFilter) 
                {
                    jQuery('#' + idElement + ' > .buttonAddFilter').show();
                }

                jQuery('#' + idElement + ' > .buttonSubmitFilter').show();  
            }

            function enabledFirstRelationalOperator()
            {
                jQuery('#' + idElement + ' > .relationalOperator > select > option').each(function(a,b)
                {
                    var d =  jQuery(b).prop('disabled');

                    if(!d)
                    {
                        jQuery(b).prop('selected',true);
                        enabledButtons();
                        //defaultSearchShow(false);
                        return false;
                    }
                });
            }    

            function enabledRelationalOperators(filterFieldValue)
            {
                jQuery('#' + idElement + ' > .relationalOperator > select option').eq(0).prop('selected',true);

                if (filterFieldValue.trim() === '' || filterFieldValue.trim() === null)
                {
                    jQuery('#' + idElement + ' > .relationalOperator > select').prop('disabled', true);
                }
                else
                {
                    jQuery('#' + idElement + ' > .relationalOperator > select').prop('disabled', false);

                    if (!jQuery.isEmptyObject(settings.allowedRelationalOperators))
                    {
                        jQuery('#' + idElement + ' > .relationalOperator > select > option').each(function (e, r)
                        {
                            if (typeof settings.allowedRelationalOperators[filterFieldValue] !== 'undefined')
                            {
                                var allowed = settings.allowedRelationalOperators[filterFieldValue];

                                jQuery(r).prop('disabled', false).css({ 'color': 'inherit' });

                                if (jQuery.inArray(jQuery(r).val().trim(), allowed) === -1)
                                {
                                    jQuery(r).prop('disabled', true).css({ 'color': '#888888' });
                                }
                            }
                        });
                    }
                }
                
            }

            function getObjectFilter() {
                var json = jQuery.base64.decode(jQuery('#' + idElement + ' > .filterData').val());

                var jsonObject = getJson(json);

                if (typeof jsonObject !== 'object') {
                    console.log('the variable {json} is not an object instance!');

                    return false;
                }

                return jsonObject;
            }

            function addFilter() {

                if (validateFilterTypeValue() === false) {
                    console.log('data type value is invalid!');

                    return false;
                }

                var jsonObject = getObjectFilter();
               
                if (!jsonObject) {
                    return false;
                }
                //   console.log(strCommand);
                var command =
                {
                    'logicalOperator': logicalOperatorValue,
                    'field': filterFieldValue,
                    'relationalOperator': relationalOperatorValue,
                    'value': formatedElement,
                    'strCommand': strCommand,
                    'strCommandParameter': strCommandParameter,
                    'strDisplay': strDisplay,
                    'doubleParameter': 0,
                    'position': position,
                    'type': type,
                    'typeLength': length[type],
                    'order': Object.keys(jsonObject.filter).length
                };
           //     console.log(command);return false;
                if (typeof jsonObject.filter[keyParam] === 'undefined') 
                {
                    jsonObject.filter[keyParam] = command;

                    jsonObject.parameters[keyParam] =
                    {
                        'param': keyParam,
                        'value': formatedElement,
                        'scapedValue': valueSelected,
                        'type': type,
                        'typeLength': length[type],
                    };

                    jsonObject.order[command.order] = keyParam;

                    if (valueSelected2 != null && keyParam2 != '') 
                    {
                        var command2 =
                        {
                            'logicalOperator': logicalOperatorValue,
                            'field': filterFieldValue,
                            'relationalOperator': relationalOperatorValue,
                            'value': formatedElement2,
                            'strCommand': strCommand,
                            'strCommandParameter': strCommandParameter,
                            'strDisplay': strDisplay,
                            'doubleParameter': 2,
                            'position': position,
                            'type': type,
                            'typeLength': length[type],
                            'order': Object.keys(jsonObject.filter).length
                        };

                        jsonObject.filter[keyParam].doubleParameter = 1;

                        jsonObject.parameters[keyParam2] =
                        {
                            'param': keyParam2,
                            'value': formatedElement2,
                            'scapedValue': valueSelected2,
                            'type': type,
                            'typeLength': length[type],
                        };

                        jsonObject.filter[keyParam2] = command2;
                        jsonObject.order[command2.order] = keyParam2;
                    }

                    
                    jsonObject.commandText += command.strCommand;
                    jsonObject.commandParameter += command.strCommandParameter;
                    jsonObject.logicalOperator = jQuery('select[name="' + idElement + '-' + 'logicalOperator"]').val();
                    jsonObject.filterField = jQuery('select[name="' + idElement + '-' + 'filterField"]').val();
                    jsonObject.relationalOperator = jQuery('select[name="' + idElement + '-' + 'RelationalOperator"]').val();
            //console.log(jsonObject);return false;
                    jQuery('#' + idElement + ' > .logicalOperator > select option').eq(1).prop('disabled', false);
                    // console.log(keyParam);
                    jQuery('#' + idElement + ' > .ActiveFilters').append('<span class="label-ticket"><div>' + strDisplay + '</div><button value="' + keyParam + '"><img src="' + settings.baseUrl + '/HarpFilter/img/x.png" width="16px"></button></span>');
                    jQuery('#' + idElement + ' > .ActiveFilters').show();
                    jQuery('#' + idElement + ' > .filterData').val(jQuery.base64.encode(JSON.stringify(jsonObject)));
                    if(settings.btnCleanAllFilters)
                    {
                        jQuery('#' + idElement + ' > .cleanAllFilter').show();
                    }
                }
                      //  console.log(jsonObject);return false;

                /*
                 * ADICIONAR OPÇÃO DE LIKE NO INICIO E NO FINAL E TROCAR CONTÉM TEXTO POR CONTENDO TEXTO.
                 * CHECKBOX TROCAR O QUE MOSTRA NO TICKET QUE É O VALOR PARA O TEXTO DO CHECKBOX
                 * OCULTAR O INPUT OU O CHECKBOX DEPOIS DE ADICIONAR O FILTRO
                 * LIMPAR O VALOR O FILTRO
                 * ADICIONAR O REMOVER FILTRO
                 */

                //  console.log(jsonObject);    

                return jsonObject;
            }

            function normalizeCommand(jsonObject) {
                if (jQuery.isEmptyObject(jsonObject.filter)) {
                    return jsonObject;
                }

                var and = (jsonObject.commandText).substring(0, 4);

                var afterCommand = (jsonObject.commandText).substring(4);

                afterCommand = afterCommand.replace('(', '').replace(')', '');

                jsonObject.commandText = and + '(' + afterCommand + ')';

                and = (jsonObject.commandParameter).substring(0, 4);

                afterCommand = (jsonObject.commandParameter).substring(4);

                afterCommand = afterCommand.replace('(', '').replace(')', '');

                jsonObject.commandParameter = and + '(' + afterCommand + ')';

                return jsonObject;
            }



            jQuery('body').on('click', '#' + idElement + ' .ActiveFilters .label-ticket button', function (e) {
                var jsonObject = getObjectFilter();

                if (!jsonObject) {
                    return false;
                }

                if (typeof jsonObject.filter[jQuery(this).val()] != 'undefined') {
                    var filter = jsonObject.filter[jQuery(this).val()];

                    var count = Object.keys(jsonObject.filter).length;

                    // console.log(filter);return false;
                    if (filter.order == 0 && ((count == 1) || (count == 2 && filter.doubleParameter == 1))) {  //console.log('ok' + jsonObject.filter,count);return false;
                        jsonObject = { filter: {}, parameters: {}, commandText: '', commandParameter: '', order: {}, logicalOperator: '', filterField: '', relationalOperator: '', request: '0' };
                    }
                    else if (filter.order == 0 && count > 1) {
                        if (filter.doubleParameter == 0) {
                            var regExp = /\(([^)]+)\)/;
                            var matches = regExp.exec(jsonObject.commandText);
                            var matches2 = regExp.exec(jsonObject.commandParameter);

                            if (matches != null && matches2 != null && matches.length > 0 && matches2.length > 0) {
                                var stc = filter.strCommand.trim().substring(3).trim();
                                var stp = filter.strCommandParameter.trim().substring(3).trim();

                                var pCommandText = matches[1].replace(stc, '').trim();
                                var pCommandParameter = matches2[1].replace(stp, '').trim();

                                jsonObject.commandText = ' AND ' + '(' + pCommandText.substring(3) + ')';
                                jsonObject.commandParameter = ' AND ' + '(' + pCommandParameter.substring(3) + ')';

                            }
                            else {
                                jsonObject.commandText = ' ' + jsonObject.commandText.replace(filter.strCommand.trim(), '') + ' ';
                                jsonObject.commandParameter = jsonObject.commandParameter.replace(filter.strCommandParameter.trim(), '');

                                jsonObject.commandText = ' AND ' + jsonObject.commandText.trim().substring(3);
                                jsonObject.commandParameter = ' AND ' + jsonObject.commandParameter.trim().substring(3);
                            }

                            delete jsonObject.order[jsonObject.filter[jQuery(this).val()].order];
                            delete jsonObject.filter[jQuery(this).val()];
                            delete jsonObject.parameters[jQuery(this).val()];
                        }
                        else {
                            var stp = filter.strCommandParameter.trim().substring(3).trim();
                            var stc = filter.strCommand.trim().substring(3).trim();
                            jsonObject.commandParameter = jsonObject.commandParameter.replace(stp, '');
                            jsonObject.commandText = jsonObject.commandText.replace(stc, '');

                            //  console.log(jsonObject.commandParameter,jsonObject.commandText);
                            // jsonObject.commandParameter = jsonObject.commandParameter.replace('AND(AND','AND(').replace('AND( AND','AND(').replace('AND(  AND','AND(').replace('AND(   AND','AND(').replace('AND(OR','AND(').replace('AND( OR','AND(').replace('AND(  OR','AND(').replace('AND(   OR','AND(');
                            //   jsonObject.commandText = jsonObject.commandText.replace('AND(AND','AND(').replace('AND( AND','AND(').replace('AND(  AND','AND(').replace('AND(   AND','AND(').replace('AND(OR','AND(').replace('AND( OR','AND(').replace('AND(  OR','AND(').replace('AND(   OR','AND(');                          
                            //                            
                            //                            console.log(jsonObject.commandParameter);
                            //                            
                            //                            
                            var parts = jsonObject.commandParameter.trim().split('(');
                            //pegar o operador e o espaço à direita
                            var opr = parts[1].trim().substring(0, 4);



                            var opr1 = parts[1].trim().substring(0, 3);

                            jsonObject.commandParameter = parts[1].trim();

                            var operators = ['AND ', 'OR '];
                            // console.log(opr,opr1);return false;
                            if (jQuery.inArray(opr, operators) != -1) {
                                jsonObject.commandParameter = jsonObject.commandParameter.trim().replace(opr, '');
                            }
                            else if (jQuery.inArray(opr1, operators) != -1) {
                                jsonObject.commandParameter = jsonObject.commandParameter.trim().replace(opr1, '');
                            }

                            jsonObject.commandParameter = ' AND ( ' + jsonObject.commandParameter;

                            // console.log(jsonObject.commandParameter);return false;
                            var parts = jsonObject.commandText.trim().split('(');
                            //pegar o operador e o espaço à direita
                            var opr = parts[1].trim().substring(0, 4);

                            var opr1 = parts[1].trim().substring(0, 3);

                            jsonObject.commandText = parts[1].trim();

                            if (jQuery.inArray(opr, operators) != -1) {
                                jsonObject.commandText = jsonObject.commandText.trim().replace(opr, '');
                            }
                            else if (jQuery.inArray(opr1, operators) != -1) {
                                jsonObject.commandText = jsonObject.commandText.trim().replace(opr1, '');
                            }


                            jsonObject.commandtext = ' AND ( ' + jsonObject.commandText;

                            keyParam2 = '@p2' + jQuery(this).val().substring(2) + '-1';

                            var filter2 = jsonObject.filter[keyParam2];

                            var ord1 = filter.order;
                            var ord2 = filter2.order;
                            delete jsonObject.order[ord1];
                            delete jsonObject.order[ord2];
                            delete jsonObject.filter[jQuery(this).val()];
                            delete jsonObject.filter[keyParam2];
                            delete jsonObject.parameters[jQuery(this).val()];
                            delete jsonObject.parameters[keyParam2];
                            //   console.log(jsonObject);return false;
                        }
                    }
                    else {
                        jsonObject.commandText = ' ' + jsonObject.commandText.trim().replace(filter.strCommand.trim(), '') + ' ';

                        jsonObject.commandParameter = ' ' + jsonObject.commandParameter.trim().replace(filter.strCommandParameter.trim(), '') + ' ';

                        delete jsonObject.order[jsonObject.filter[jQuery(this).val()].order];
                        delete jsonObject.filter[jQuery(this).val()];
                        delete jsonObject.parameters[jQuery(this).val()];

                        if (filter.doubleParameter != 0) {
                            keyParam2 = '@p2' + jQuery(this).val().substring(2) + '-1';

                            var filter2 = jsonObject.filter[keyParam2];
                            delete jsonObject.order[ord2];
                            delete jsonObject.filter[keyParam2];
                            // delete jsonObject.parameters[jQuery(this).val()];
                            delete jsonObject.parameters[keyParam2];
                        }


                    }

                    var order = 0;

                    if (!jQuery.isEmptyObject(jsonObject.filter)) {
                        jsonObject.order = {};

                        jQuery.each(jsonObject.filter, function (e, r) {
                            jsonObject.filter[e].order = order;

                            jsonObject.order[order] = e;

                            ++order;
                        });

                        var spl = jsonObject.filter[jsonObject.order[0]].strDisplay.trim().match(/^(\S+)\s(.*)/);

                        if (typeof spl[1] != 'undefined') {
                            jQuery('#' + idElement + ' > .logicalOperator > select > option').each(function (i, o) {
                                var operator = jQuery(o).text().trim();

                                if (jQuery.inArray(operator, spl) != -1) {
                                    jsonObject.filter[jsonObject.order[0]].strDisplay = spl[2];
                                }
                            });
                        }
                    }

                    jsonObject.request = '0';

                    jQuery('#' + idElement + ' > .filterData').val(jQuery.base64.encode(JSON.stringify(jsonObject)));

                    jQuery(this).parent().parent().hide();

                    if (typeof (Storage) != "undefined") {
                        localStorage.setItem(currentFilter, jQuery('#' + idElement + ' > .filterData').val());
                    }

                    if (settings.loadWhileSubmit.show) {
                        loaderGif('#' + idElement + ' .ActiveFilters .label-ticket button');
                    }

                }
            });

            jQuery('#' + idElement + ' > .buttonSubmitFilter').click(function (e) 
            {
                var jsonObject = null;

                if ((tickets == null || tickets.length < 1) && jQuery(filterElementSelected).length == 0) {
                    return false;
                }
                else if (jQuery(filterElementSelected).length > 0) 
                {//console.log(tickets.length);return false;
                    jsonObject = addFilter();

                    if (!jsonObject || jsonObject == null) {
                        return false;
                    }
                }

                jsonObject = jsonObject ? jsonObject : getJson(jQuery.base64.decode(settings.jsonFilterData));

                jsonObject = normalizeCommand(jsonObject);

                jQuery('#' + idElement + ' > .filterData').val(jQuery.base64.encode(JSON.stringify(jsonObject)));

                if (typeof (Storage) != "undefined") 
                {
                    localStorage.setItem(currentFilter, jQuery('#' + idElement + ' > .filterData').val());
                }
                // console.log('load gif:'+settings.loadWhileSubmit.show);
                if (settings.loadWhileSubmit.show) 
                {
                    loaderGif('#' + idElement + ' > .buttonSubmitFilter');
                }

            });

            jQuery('#' + idElement + ' > .buttonAddFilter').click(function (e) 
            {
                e.preventDefault();

                if (!addFilter()) {
                    return false;
                }
            });

            jQuery('#' + idElement + ' > .logicalOperator > select').change(function () {
                logicalOperatorValue = jQuery(this).val();
            });
            
            

            jQuery('#' + idElement + ' > .relationalOperator > select').change(function () 
            {
                onChangeRelationalOperator(this);
            });


            

            jQuery('#' + idElement + ' > .filterField > select').change(function () 
            {
                onChangeFilterField(this);
            });
        });
    };
})(jQuery);