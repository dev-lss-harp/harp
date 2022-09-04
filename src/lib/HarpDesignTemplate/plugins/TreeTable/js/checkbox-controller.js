jQuery(document).ready(function()
{
    jQuery('.checkbox-modulo').on('click',function()
    {
        var id = jQuery(this).attr('id');
        
        if(jQuery('#'+id).is(':checked'))
        {
            jQuery('.'+id).prop('checked',true);
        }
        else
        {
            jQuery('.'+id).prop('checked',false);
        }
    });
    
    jQuery('.checkbox-grupo').on('click',function()
    {
        var id = jQuery(this).attr('id');
        
        var id_modulo = jQuery(this).attr('class').split(' ')[0];

        if(jQuery('#'+id).is(':checked'))
        {
            jQuery('.'+id).prop('checked',true);
            
            jQuery('#'+id_modulo).prop('checked',true);
        }
        else
        {
            jQuery('.'+id).prop('checked',false);
            
        }
        
        
        var is_checked = 0;
        
        jQuery('.'+id_modulo).each(function(e,r)
        {
            if(jQuery(r).is(':checked'))
            {
                ++is_checked;
            }
        });
        
        if(is_checked < 1)
        {
            jQuery('#'+id_modulo).prop('checked',false);
        }
        
    });    
    
    jQuery('.checkbox-acesso').on('click',function()
    {
        var id = jQuery(this).attr('id');
        
        var cls = jQuery(this).attr('class').split(' ');
        
        var id_modulo = cls[0];
        var id_grupo = cls[1];

        if(jQuery('#'+id).is(':checked'))
        {
            jQuery('#'+id_grupo).prop('checked',true);
            jQuery('#'+id_modulo).prop('checked',true);
        }
        
        var is_checked = 0;
        
        jQuery('.'+id_grupo).each(function(e,r)
        {
            if(jQuery(r).is(':checked'))
            {
                ++is_checked;
            }
        });

        if(is_checked < 1)
        {
            jQuery('#'+id_grupo).prop('checked',false);
        }
        
        is_checked = 0;
        
        jQuery('.'+id_modulo).each(function(e,r)
        {
            if(jQuery(r).is(':checked'))
            {
                ++is_checked;
            }
        });
        
        if(is_checked < 1)
        {
            jQuery('#'+id_modulo).prop('checked',false);
        }        
        
    });     
    
});


