<?php 
namespace Harp\lib\HarpLang;

class ExtractLang
{
    public static function parseLangs() : Array
    {
        $countryLangs = json_decode(file_get_contents(__DIR__.'/full2-country-languages-codes.json'),true);
        $langParse = [];
        $rlangs = [];
      
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',HTTP_ACCEPT_LANGUAGE, $langParse);

        if (count($langParse[1]) > 0) 
        {
            $defaultWeight = 0.9; 

         
            foreach ($langParse[1] as $k => $lang) 
            {

                if(!empty($lang))
                {
                   
                    if(isset($countryLangs[$lang]))
                    {
                        $rlangs[$k]['code'] = $lang;
                        $rlangs[$k]['lang'] = $lang;
                        $rlangs[$k]['weight'] = !empty($langParse[4][$k]) ? $langParse[4][$k] : $defaultWeight;
                        $rlangs[$k]['countryIso2'] = $countryLangs[$lang]['iso2'];
                        $defaultWeight -= 0.1;   
                    }
                }
            }
        }

        return $rlangs;
    }

}