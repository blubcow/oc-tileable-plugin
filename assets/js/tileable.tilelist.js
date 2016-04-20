/*
 * List Widget
 *
 * Dependences:
 * - Row Link Plugin (october.rowlink.js)
 */
+function ($) { "use strict";

    var ListWidget = function (element, options) {

        var $el = this.$el = $(element);

        this.options = options || {};
        this.dropzone = null;

        this.init();
        this.initUploader();
    };

    ListWidget.DEFAULTS = {
    };
    
    ListWidget.prototype.init = function()
    {
        /*
        this.$el.closest('form').bind('ajaxSuccess', function(context, data, textStatus, jqXHR){
            console.log(context);
            console.log(data);
            console.log(textStatus);
            console.log(jqXHR);
        });
        */
        var t = this;
        
        $(window).bind('ajaxUpdateComplete', function(context, data, textStatus, jqXHR){
            t.update();
        });
        
        //ajaxUpdateComplete
        
        /*
        this.$el.find('[data-control="listwidget"]')
        
        
        .closest('[data-control="listwidget"]')
        
        $(this).trigger('unchange.oc.changeMonitor');
        */
        var
            list = this.$el,
            body = this.$el.find('#list_body').eq(0),
            head = $('thead', list),
            //body = $('tbody', list),
            foot = $('tfoot', list);
            
        //------------------------------------------------------------------------

        $('input[type="checkbox"]', list).each(function(){
            var $el = $(this);
            if ($el.is(':checked'))
                $el.parents('.item').eq(0).addClass('active');
        });
        
         //------------------------------------------------------------------------

        
        list.on('change', 'input[type="checkbox"]', function(){
            var $el = $(this),
                checked = $el.is(':checked');
            
            
            /*
            $el.request($el.data('request-handler'),{data:{
                value:555
            }});
            */
            

            if (checked) {
                $el.parents('.item').eq(0).addClass('active');
            } else {
                $el.parents('.item').eq(0).removeClass('active');
            }
        });
        
        
        /**
         * delete record completely 
         */
        list.on('click', '*[data-delete-record]', function()
        {
            var x = window.confirm('You want to delete this record completely?');
            if(x){
                $(this).request( $(this).data('request-handler') ,{data:{
                    delete_model: $(this).data('delete-record')
                }});
                return true;
            }else{
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        /**
         * prevent bubbling up on overlays 
         */
        list.on('click', '*[data-click-overlay]', function(e)
        {
            e.stopPropagation();
        });
        
        /**
         * click action - main action on item click 
         */
        list.on('click', '*[data-click-target]', function(e)
        {
            var $link = $(e.currentTarget);
            var target = $link.data('click-target');
            
            // value can be "checkbox" or "http://url..."
            if(target == 'checkbox'){
                var $checkbox = $link.parents('.item').eq(0).find('input[type="checkbox"]');
                $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
            }else{
                location.href = target;
            }
        });
        
        
        //------------------------------------------------------------------------
        /*var $form = list.closest('form');
        
        list.on('click', '[data-control~="monitor-check"]', function(e)
        {
            if($form.hasClass('oc-data-changed')){
                var x = window.confirm('Your input hasnt been saved. Continue?');
                if(x){
                    return true;
                }else{
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        });*/
    };
    
    ListWidget.prototype.initUploader = function()
    {
        var $btn = this.$el.find('[data-control="upload"]').eq(0);
            
        var postUrl = $btn.data('url');
        var handler = $btn.data('handler');
        
        if(postUrl && (postUrl != '')){
            var uploaderOptions = {
                clickable: $btn.get(0),
                method: 'POST',
                url: $btn.data('url'),
                paramName: 'file_data',
                createImageThumbnails: false,
                headers: {
                    'X-OCTOBER-REQUEST-HANDLER': handler
                },
                //previewsContainer: this.$el.find('[data-control="upload-ui"] .upload-preview').get(0)
                // fallback: implement method that would set a flag that the uploader is not supported by the browser
            };
                
            this.dropzone = new Dropzone($btn.get(0), uploaderOptions);
            this.dropzone.on('queuecomplete', this.onUploadQueueComplete);
            /*
            this.dropzone.on('addedfile', this.proxy(this.uploadFileAdded));
            this.dropzone.on('totaluploadprogress', this.proxy(this.uploadUpdateTotalProgress));
            this.dropzone.on('queuecomplete', this.proxy(this.uploadQueueComplete));
            this.dropzone.on('sending', this.proxy(this.uploadSending));
            this.dropzone.on('error', this.proxy(this.uploadError));
            this.dropzone.on('success', this.proxy(this.uploadSuccess));
            */
        }
            
    };
    
    ListWidget.prototype.onUploadQueueComplete = function() {
        alert('upload complete. please reload site');
        //location.reload(true);
    };
    
    
    
    ListWidget.prototype.update = function()
    {
        // remove uploader
        if (this.dropzone){
            this.dropzone.destroy();
            this.dropzone = null;
        }
        
        this.initUploader();
    };
    
    
    
    
    

    ListWidget.prototype.getChecked = function() {
        var
            list = this.$el,
            body = $('tbody', list)

        return  $('.list-checkbox input[type="checkbox"]', body).map(function(){
            var $el = $(this)
            if ($el.is(':checked'))
                return $el.val()
        }).get();
    }

    ListWidget.prototype.toggleChecked = function(el) {
        var $checkbox = $('.list-checkbox input[type="checkbox"]', $(el).closest('tr'))
        $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change')
    }

    // LIST WIDGET PLUGIN DEFINITION
    // ============================

    var old = $.fn.listWidget

    $.fn.listWidget = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result

        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.listwidget')
            var options = $.extend({}, ListWidget.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.listwidget', (data = new ListWidget(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
      }

    $.fn.listWidget.Constructor = ListWidget

    // LIST WIDGET NO CONFLICT
    // =================

    $.fn.listWidget.noConflict = function () {
        $.fn.listWidget = old
        return this
    }

    // LIST WIDGET HELPERS
    // =================

    if ($.oc === undefined)
        $.oc = {}

    $.oc.listToggleChecked = function(el) {
        $(el)
            .closest('[data-control="listwidget"]')
            .listWidget('toggleChecked', el)
    }

    $.oc.listGetChecked = function(el) {
        return $(el)
            .closest('[data-control="listwidget"]')
            .listWidget('getChecked')
    }

    // LIST WIDGET DATA-API
    // ==============

    $(document).render(function(){
        $('[data-control="tilelistwidget"]').listWidget();
    })

}(window.jQuery);