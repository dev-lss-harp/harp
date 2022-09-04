var HarpPluginTreeList = jQuery.noConflict();

HarpPluginTreeList(document).ready(function () 
{  
    HarpPluginTreeList('.HarpPluginTreeList li > ul').each(function(i) 
    {
        var parent_li = HarpPluginTreeList(this).parent('li');
        
        var sub_ul = HarpPluginTreeList(this).remove();
        
        parent_li.wrapInner('<a/>').find('a').click(function(event) 
        {
               if (!HarpPluginTreeList(event.target).closest(".not-open-treelist").length) 
               {
                    sub_ul.toggle();
               } 
               
               event.preventDefault();
        });
        
        parent_li.append(sub_ul);
    });
    //Ocultar Os Filhos
    HarpPluginTreeList('.HarpPluginTreeListUl ul').hide();
});
