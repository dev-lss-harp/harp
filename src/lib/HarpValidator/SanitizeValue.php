<?php
namespace Harp\lib\HarpValidator;

class SanitizeValue
{
    public static function filterSanitizeString(string $string): string
    {
        $str = preg_replace('/\x00|<[^>]*>?/','', $string);
        $str = trim(addslashes($str));
        return $str;
    }

    public static function &email(&$email)
    {
        $email = filter_var($email,FILTER_SANITIZE_EMAIL);
        
        return $email;
    }
       
    public static function &encodeSpecialChars($params,$quoteType = ENT_QUOTES)
    {
        
        if(is_array($params))
        {
            foreach($params as $i => $v)
            {
                $params[$i] = htmlspecialchars($v,$quoteType);
            }            
        }
        else if(is_object($params))
        {
            foreach($params as $i => $v)
            {
                $params->$i = htmlspecialchars($v,$quoteType);
            }             
        }
        else
        {
            $params = htmlspecialchars($params,$quoteType);
        }

        return $params;        
    }
    
    public static function &decodeSpecialChars($Params,$QuoteType = ENT_QUOTES)
    {
        if(is_array($Params))
        {
            foreach($Params as $i => $v)
            {
                $Params[$i] = html_entity_decode($v,$QuoteType,'UTF-8');
            }            
        }
        else if(is_object($Params))
        {
            foreach($Params as $i => $v)
            {
                $Params->$i = html_entity_decode($v,$QuoteType,'UTF-8');
            }             
        }
        else
        {
            $Params = html_entity_decode($Params,$QuoteType,'UTF-8');
        }

        return $Params;        
    }    
    
    public static function &removeSanitizeString($s,$StripSpecialChars = true,$DecodeTokenTags = true)
    {
            $s = stripslashes($s); 
            
            if($StripSpecialChars)
            {
                $s = html_entity_decode($s,ENT_QUOTES,'UTF-8');

                $s = ($DecodeTokenTags) ? str_replace(array('&lt;','&gt;'),array('<','>'),$s) : $s; 
            }

            return $s;
    }
    
    public static function &removeSanitizeStringAll($values,$StripSpecialChars = true)
    {
        if(is_array($values))
        {
            foreach($values as $i => $v)
            {
                $values[$i] = $this->RemoveSanitizeStringAll($v,$StripSpecialChars);
            }            
        }
        else if(is_object($values))
        {
            foreach($values as $i => $v)
            {
                $values->$i = $this->RemoveSanitizeStringAll($v,$StripSpecialChars);
            }             
        }
        else
        {
            $this->RemoveSanitizeString($values,$StripSpecialChars);
        }

        return $values;
    }    
        
    
    public static function onlyNumbers($str)
    {
        $onlyNumber = preg_replace('#[^0-9]#i','',$str);
        
        return (int) $onlyNumber;
    }
    
    public static function replaceAccentsString($Str)
    {
        //Quando intl não estiver funcionando copiar as seguintes dlls para apache/bin
        //icudt57.dll,icuin57.dll,icuio57.dll todas começando com ic
        if(extension_loaded('intl'))
        {
            $Str = preg_replace('/\p{M}/u', '', \Normalizer::normalize($Str,\Normalizer::FORM_D));
        }

        $Accented = Array
        (
            "À","Á","Â","Ã","Ä","Å","Æ", "Ç","È","É","Ê","Ë","Ì","Í","Î","Ï","Ð","Ñ","Ò","Ó","Ô","Õ","Ö","Ø","Ù","Ú","Û","Ü","Þ", "ß", "à","á","â","ã","ä","å","æ", "ç","è","é","ê","ë","ì","í","î","ï","ð","ñ","ò","ó","ô","õ","ö","ø","ù","ú","û","ü","þ", "Ā","ā","Ă","ă","Ą","ą","Ć","ć","Ĉ","ĉ","Ċ","ċ","Č","č","Ď","ď","Đ","đ","Ē","ē","Ĕ","ĕ","Ė","ė","Ę","ę","Ě","ě","Ĝ","ĝ","Ğ","ğ","Ġ","ġ","Ģ","ģ","Ĥ","ĥ","Ħ","ħ","Ĩ","ĩ","Ī","ī","Ĭ","ĭ","Į","į","İ","ı","Ĳ", "ĳ", "Ĵ","ĵ","Ķ","ķ","Ĺ","ĺ","Ļ","ļ","Ľ","ľ","Ŀ","ŀ","Ł","ł","Ń","ń","Ņ","ņ","Ň","ň","ŉ","Ō","ō","Ŏ","ŏ","Ő","ő","Œ", "œ", "Ŕ","ŕ","Ŗ","ŗ","Ř","ř","Ś","ś","Ŝ","ŝ","Ş","ş","Š","š","Ţ","ţ","Ť","ť","Ŧ","ŧ","Ũ","ũ","Ū","ū","Ŭ","ŭ","Ů","ů","Ű","ű","Ų","ų","ſ","ƒ","Ǆ", "ǅ", "ǆ", "Ǉ", "ǈ", "ǉ", "Ǌ", "ǋ", "ǌ", "Ǳ", "ǲ", "ǳ", "Ș","ș","Ț","ț","Ḁ","ḁ","Ḃ","ḃ","Ḅ","ḅ","Ḇ","ḇ","Ḉ","ḉ","Ḋ","ḋ","Ḍ","ḍ","Ḏ","ḏ","Ḑ","ḑ","Ḓ","ḓ","Ḕ","ḕ","Ḗ","ḗ","Ḙ","ḙ","Ḛ","ḛ","Ḝ","ḝ","Ḟ","ḟ","Ḡ","ḡ","Ḣ","ḣ","Ḥ","ḥ","Ḧ","ḧ","Ḩ","ḩ","Ḫ","ḫ","Ḭ","ḭ","Ḯ","ḯ","Ḱ","ḱ","Ḳ","ḳ","Ḵ","ḵ","Ḷ","ḷ","Ḹ","ḹ","Ḻ","ḻ","Ḽ","ḽ","Ḿ","ḿ","Ṁ","ṁ","Ṃ","ṃ","Ṅ","ṅ","Ṇ","ṇ","Ṉ","ṉ","Ṋ","ṋ","Ṍ","ṍ","Ṏ","ṏ","Ṑ","ṑ","Ṓ","ṓ","Ṕ","ṕ","Ṗ","ṗ","Ṙ","ṙ","Ṛ","ṛ","Ṝ","ṝ","Ṟ","ṟ","Ṡ","ṡ","Ṣ","ṣ","Ṥ","ṥ","Ṧ","ṧ","Ṩ","ṩ","Ṫ","ṫ","Ṭ","ṭ","Ṯ","ṯ","Ṱ","ṱ","Ṳ","ṳ","Ṵ","ṵ","Ṷ","ṷ","Ṹ","ṹ","Ṻ","ṻ","Ṽ","ṽ","Ṿ","ṿ","Ẁ","ẁ","Ẃ","ẃ","Ẅ","ẅ","Ẇ","ẇ","Ẉ","ẉ","Ẋ","ẋ","Ẍ","ẍ","Ẏ","ẏ","Ẑ","ẑ","Ẓ","ẓ","Ẕ","ẕ","ẖ","ẗ","ẘ","ẙ","ẚ","ẞ","Ạ","ạ","Ả","ả","Ấ","ấ","Ầ","ầ","Ẩ","ẩ","Ẫ","ẫ","Ậ","ậ","Ắ","ắ","Ằ","ằ","Ẳ","ẳ","Ẵ","ẵ","Ặ","ặ","Ẹ","ẹ","Ẻ","ẻ","Ẽ","ẽ","Ế","ế","Ề","ề","Ể","ể","Ễ","ễ","Ệ","ệ","Ỉ","ỉ","Ị","ị","Ọ","ọ","Ỏ","ỏ","Ố","ố","Ồ","ồ","Ổ","ổ","Ỗ","ỗ","Ộ","ộ","Ớ","ớ","Ờ","ờ","Ở","ở","Ỡ","ỡ","Ợ","ợ","Ụ","ụ","Ủ","ủ","Ứ","ứ","Ừ","ừ","Ử","ử","Ữ","ữ","Ự","ự","Ỳ","ỳ","Ỵ","ỵ","Ỷ","ỷ","Ỹ","ỹ"
        );
        
        $Normalizated = Array
        (
            "A","A","A","A","A","A","AE","C","E","E","E","E","I","I","I","I","D","N","O","O","O","O","O","O","U","U","U","U","Th","ss","a","a","a","a","a","a","ae","c","e","e","e","e","i","i","i","i","d","n","o","o","o","o","o","o","u","u","u","u","th","A","a","A","a","A","a","C","c","C","c","C","c","C","c","D","d","D","d","E","e","E","e","E","e","E","e","E","e","G","g","G","g","G","g","G","g","H","h","H","h","I","i","I","i","I","i","I","i","I","i","IJ","ij","J","j","K","k","L","l","L","l","L","l","L","l","L","l","N","n","N","n","N","n","n","O","o","O","o","O","o","OE","oe","R","r","R","r","R","r","S","s","S","s","S","s","S","s","T","t","T","t","T","t","U","u","U","u","U","u","U","u","U","u","U","u","s","f","DZ","Dz","dz","LJ","Lj","lj","NJ","Nj","nj","DZ","Dz","dz","S","s","T","t","A","a","B","b","B","b","B","b","C","c","D","d","D","d","D","d","D","d","D","d","E","e","E","e","E","e","E","e","E","e","F","f","G","g","H","h","H","h","H","h","H","h","H","h","I","i","I","i","K","k","K","k","K","k","L","l","L","l","L","l","L","l","M","m","M","m","M","m","N","n","N","n","N","n","N","n","O","o","O","o","O","o","O","o","P","p","P","p","R","r","R","r","R","r","R","r","S","s","S","s","S","s","S","s","S","s","T","t","T","t","T","t","T","t","U","u","U","u","U","u","U","u","U","u","V","v","V","v","W","w","W","w","W","w","W","w","W","w","X","x","X","x","Y","y","Z","z","Z","z","Z","z","h","t","w","y","a","B","A","a","A","a","A","a","A","a","A","a","A","a","A","a","A","a","A","a","A","a","A","a","A","a","E","e","E","e","E","e","E","e","E","e","E","e","E","e","E","e","I","i","I","i","O","o","O","o","O","o","O","o","O","o","O","o","O","o","O","o","O","o","O","o","O","o","O","o","U","u","U","u","U","u","U","u","U","u","U","u","U","u","Y","y","Y","y","Y","y","Y","y"
        );
        
        return str_replace($Accented,$Normalizated,$Str);
    }
    
    
    public static function removeAllImagesFromString($str)
    {
        $newStr = $str;
        
        if(is_string($str))
        {
            $newStr = preg_replace('`<img.*?[^>]src="(.*?)".*?[^>]>`s','',$str);
        }
        
        return $newStr;
    }
       
}
