//Procura por todos os filhos do elemento e a depender do estado marca-os ou desmarca-os
function getChildrens(id,prop)
{
       var childrens = jQuery('[data-parent-id="'+id+'"]');

       if(childrens.length > 0)
       {
           jQuery.each(childrens,function(x,m)
           {
               id = jQuery(m).attr('id').replace('checkbox_','').trim();
               
               jQuery(m).prop('checked',prop);
               
               getChildrens(id,prop);
           });
       }
}
//Procura por todos os pais do elemento e a depender do estado marca-os ou desmarca-os
//se o elemento esta no estado checked então então todos os pais são marcados
//se o elemento esta no estado unchecked então verifica-se se o pai ainda possui outros filhos marcados
//caso contrário desmarca o pai
function getParents(parentId,prop)
{
       var parents = jQuery('[id="checkbox_'+parentId+'"]');
       
       var l = jQuery('[data-parent-id="'+parentId+'"]:checked').length;
       
       if(parents.length > 0)
       {
           jQuery.each(parents,function(x,m)
           {
               parentId = jQuery(m).attr('data-parent-id');

               if(l < 1 && !prop)
               {
                   jQuery(m).prop('checked',prop);
               }
               else if(prop)
               {
                   jQuery(m).prop('checked',prop);
               }

               getParents(parentId,prop);
           });
       }
}
jQuery(document).ready(function()
{
   jQuery('body').on('click','.checkbox_pluginTableTree',function()
   {
       var parentId = jQuery(this).attr('data-parent-id');
       var id = jQuery(this).attr('id').replace('checkbox_','').trim();
       
       if(jQuery(this).is(':checked'))
       {
           getChildrens(id,true);
           getParents(parentId,true);
       }
       else
       {
           getChildrens(id,false);
           getParents(parentId,false);         
       }
   });   
});

