/**
 * This plugin basically makes alerts more...responsive
 *
 * @example
 *
 * We can init all alerts like so: `$('.alert').summarise();`. Doing this makes it easier to come back later and make it do our bidding,
 * however, we don't have to init them. We can just call: `$('.alert').summarise({},'error', 'Oops!');` to make an alert.
 *
 * ## More examples
 *
 * Let's set some content:
 *
 * $('.alert').summarise('content', {message: 'Sorry it did not succeed; please try again', list: ['message1','message2']});
 *
 * Or just set an error:
 *
 * $('.alert').summarise('set', 'error', 'Sorry but it did not succeed; please try again.');
 *
 * To close the alert
 *
 * $('.alert').alert('close');
 *
 * Have Fun!!
 */
;(function($, window, document, undefined){

    var options = {
            'base_class' : 'alert',

            'error_class' : 'alert-danger',
            'success_class' : 'alert-success',
            'warning_class' : 'alert-warning',
            'info_class' : 'alert-info',

            'tpl_close' : '\
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">\
            <span aria-hidden="true">&times;</span>\
        </button>',
        },
        methods = {
            init : function(opts, type, content){

                settings=$.extend(true, {}, options, opts);

                return this.each(function(){
                    data = $(this).data('summarise');
                    $this=$(this);

                    if(!data){
                        $this.data('summarise', {
                            '_' : this,
                            'options' : settings
                        });

                        if(!$this.hasClass(settings.base_class)){
                            $this.addClass(settings.base_class);
                        }
                        $this.addClass('summarise-alert');

                        methods.type(type,$this);
                        methods.content(content,$this);
                    }
                });
            },
            destroy : function(){
                $this=$(this);
                data=$this.data('summarise');

                // TODO Make this more complete
                if(data)
                    $this.removeData('summarise');
            },
            set : function(type, content){
                methods.type(type,$(this));
                methods.content(content,$(this));
            },
            type : function(type,el){
                $this=el||$(this);
                settings=$.extend(true, {}, options, $this.data('summarise').options);
                if(type!==null&&type!==undefined){
                    cssClass=settings[type+'_class'];
                    $this.removeClass([
                        settings['error_class'],
                        settings['success_class'],
                        settings['warning_class'],
                        settings['info_class']
                    ].join(' ')).addClass(cssClass);
                }
            },
            content : function(content,el){
                $this=el||$(this);
                settings=$.extend(true, {}, options, $this.data('summarise').options);
                if(content!==null&&content!==undefined){

                    $this.html('');

                    if(settings.tpl_close!==null&&settings.tpl_close!==undefined)
                        $this.append($(settings.tpl_close));

                    if(typeof content == "object"){
                        if(content['message']!==undefined&&content['message']!==null)
                            $this.append(content['message']);
                        if(content['list']!==undefined&&content['list']!==null){
                            var list=$('<ul/>').appendTo($this);
                            $.each(content['list'], function(i, v){
                                list.append($('<li/>').text(v));
                            });
                        }
                    }else
                        $this.append(content);
                    $this.css({display:'block'});
                }
            },
            reset : function(){
                reset($(this));
            },
            close : function(){
                $this=$(this);
                reset($this);
                $this.css({display:'none'});
            },
            focus: function(){
                $("html, body").animate({ scrollTop: $(this).offset().top }, "fast");
            }
        },
        reset = function(el){
            $this=el;
            settings=$.extend(true, {}, options, $this.data('summarise').options);
            $this.removeClass([
                settings['error_class'],
                settings['success_class'],
                settings['warning_class'],
                settings['info_class']
            ].join(' ')).html('');
        };

    $(document).on('click', '.summarise-alert .close', function(event){
        event.preventDefault();
        $(this).parents('.summarise-alert').summarise('close');
    });

    $.fn.summarise = function(method) {
        // Method calling logic
        if ( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' + method + ' does not exist on jQuery.summarise' );
        }
    };

})(jQuery, window, document);