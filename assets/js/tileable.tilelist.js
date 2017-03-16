/*
 * List Widget
 *
 * Dependences:
 * - Row Link Plugin (october.rowlink.js)
 */
+function ($) { "use strict";

    var TileListWidget = function (element, options) {

        var $el = this.$el = $(element);

        this.options = options || {};
        this.dropzone = null;
        this.sortable = null;

        this.init();
        this.initUploader();
    };

    TileListWidget.DEFAULTS = {
    };
    
    TileListWidget.prototype.init = function()
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
        
        $(window).bind('ajaxUpdateComplete', function(e, context, data, textStatus, jqXHR){
            t.update();
        });
        
        //ajaxUpdateComplete
        
        /*
        this.$el.find('[data-control="tilelistwidget"]')
        
        
        .closest('[data-control="tilelistwidget"]')
        
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

        /**
         * CHECKBOX 
         */
        this.$el.on('change', 'input[type="checkbox"]', function(){
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
         * MANUAL SORT 
         */
        this.$el.on('click', '*[data-sortinput]', function(e)
        {
            var $item = $(e.target).parents('.item').eq(0);
            var id = $item.attr('id');
            
            //
            var sort = window.prompt('Enter a sort position', $item.data('custom-sort'));
            sort = parseInt(sort);
            if(sort<=0){
                alert('this number is not valid');
            }
            
            //
            $.proxy(t.onSortRecord(id, (sort-1)), t);
        });
        
        /**
         * DELETE record completely 
         */
        this.$el.on('click', '*[data-delete-record]', function()
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
        
        // style - prevent bubbling up on overlays 
        this.$el.on('click', '*[data-click-overlay]', function(e)
        {
            e.stopPropagation();
        });
        
        /**
         * click action - main action on item click 
         */
        this.$el.on('click', '*[data-click-target]', function(e)
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
        
        if (this.options.isSortable) {
            this.bindSortable()
        }
    };
    
    TileListWidget.prototype.initUploader = function()
    {
        var $btn = this.$el.find('*[data-control="upload"]').eq(0);
            
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
    
    TileListWidget.prototype.onUploadQueueComplete = function() {
        alert('upload complete. please reload site');
        //location.reload(true);
    };
    
    
    
    /*
    //
    // update
    TileListWidget.prototype.updateBody = function()
    {
        console.log('updateBody');
        
        this.bindSortable();
    }
    */
    
    
    
    //
    // Sorting
    //

    TileListWidget.prototype.bindSortable = function() {
        
        console.log('bindSortable');
        
        var _self = this;
        this.sortable = Sortable.create(this.$el.find('.tileable-list').get(0), {
            onUpdate: function (evt/**Event*/){
                 //var item = evt.item; // the current dragged HTMLElement
                 //_self.onSortRecords(_self);
                 
                 var item = evt.item;
                 var id = $(item).attr('id');
                 var index = $(item).index();			     
                 
                 $.proxy(_self.onSortRecord(id, index), _self);
            }
        });
        
        
        
        /*
        var placeholderEl = $('<div class="item active placeholder"><div class="wrapper">adfasdf</div></div>');
        var _self = this;
        
        this.$el.find('.tileable-list').sortable({
            itemSelector: 'div.item',
            containerSelector: '.tileable-list',
            nested: false,
            tolerance: 50,
            placeholder: placeholderEl,
            handle: '.sort-button',
            forcePlaceholderSize: true,
            onDrop: function ($item, container, _super, event){
                //console.log($item);
                //console.log(container);
                //console.log(_super);
                //_self.onSortRecords(_self);
                
                _super($item, container);
                
                _self.onSortRecords(_self);
            },
            distance: 10
        })
        */
    }
    
    TileListWidget.prototype.onSortRecord = function(id, index)
    {
        var _ = this;
        //
        if(this.options.sortHandler)
        {
            this.$el.request(this.options.sortHandler, {
                data: {
                    'id': id,
                    'index': index 
                },
                complete: function(){
                    _.update();
                }
            });
        }
    }
    
    /*
    TileListWidget.prototype.onSortRecords = function(_s) {
        var _self = _s;
        //console.log(_self.options);
        if (_self.options.sortHandler) {

            //Build an object of ID:ORDER
            var orderData = {}
            
            var index = _self.$el.first('.item').attr('id');
            _self.$el.find('.item')
                .each(function(index){
                    var id = $(this).attr('id')
                    orderData[id] = index + 1
                });
                        
            
            _self.$el.request(this.options.sortHandler, {
                data: { sortOrder: orderData },
                complete: function(){
                    _self.update();
                }
            })
        }
    }
    */
    
    //
    //
    //
    
    TileListWidget.prototype.update = function()
    {
        
        console.log('UPDATE');
        
        // remove sortable
        if(this.sortable){
            this.sortable.destroy();
            this.sortable = null;
        }
        if (this.options.isSortable) {
            this.bindSortable()
        }
        
        // remove uploader
        if (this.dropzone){
            this.dropzone.destroy();
            this.dropzone = null;
        }
        this.initUploader();
    };
    
    TileListWidget.prototype.getChecked = function() {
        var
            list = this.$el,
            body = $('tbody', list)

        return  $('.list-checkbox input[type="checkbox"]', body).map(function(){
            var $el = $(this)
            if ($el.is(':checked'))
                return $el.val()
        }).get();
    }

    TileListWidget.prototype.toggleChecked = function(el) {
        var $checkbox = $('.list-checkbox input[type="checkbox"]', $(el).closest('tr'))
        $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change')
    }

    // LIST WIDGET PLUGIN DEFINITION
    // ============================

    var old = $.fn.tileListWidget

    $.fn.tileListWidget = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result

        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.tilelistwidget')
            var options = $.extend({}, TileListWidget.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.tilelistwidget', (data = new TileListWidget(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
      }

    $.fn.tileListWidget.Constructor = TileListWidget

    // LIST WIDGET NO CONFLICT
    // =================

    $.fn.tileListWidget.noConflict = function () {
        $.fn.tileListWidget = old
        return this
    }

    // LIST WIDGET HELPERS
    // =================

    if ($.oc === undefined)
        $.oc = {}

    $.oc.listToggleChecked = function(el) {
        $(el)
            .closest('[data-control="tilelistwidget"]')
            .tileListWidget('toggleChecked', el)
    }

    $.oc.listGetChecked = function(el) {
        return $(el)
            .closest('[data-control="tilelistwidget"]')
            .tileListWidget('getChecked')
    }

    // LIST WIDGET DATA-API
    // ==============

    $(document).render(function(){
        $('[data-control="tilelistwidget"]').tileListWidget();
    });
    
}(window.jQuery);
