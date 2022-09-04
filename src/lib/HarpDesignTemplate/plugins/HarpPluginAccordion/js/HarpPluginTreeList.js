var HarpPluginTreeList = jQuery.noConflict();

HarpPluginTreeList(document).ready(function () 
{  
        function CreateCookie(key,value,days) 
         {
            var date = new Date();

            // Default at 365 days.
            days = days || 365;

            // Get unix milliseconds at current time plus number of days
            date.setTime(+ date + (days * 86400000)); //24 * 60 * 60 * 1000
            //console.log(date.toGMTString());
            return HarpPluginTreeList.cookiekey + "=" + value + "; expires=" + date.toGMTString() + "; path=/";
        };
        
      //  CreateCookie('teste','x');
    
    jQuery.ajaxSetup
    ({
        cache: false
    });
    
    function stateChanged(nodes,nodesJson) 
    {
        var t = nodes[0].text;
        HarpPluginTreeList.cookie('treelist-easytree',nodesJson,{ expires:1});
        
        //HarpPluginTreeList.cookie('treelist-easytree',nodesJson,{expire:0});
    }    

    HarpPluginTreeList('.treelist-easytree').easytree
    ({
        data: HarpPluginTreeList.cookie("treelist-easytree"),
        stateChanged: stateChanged,
        disableIcons:true,
        slidingTime:50
    });    
});


