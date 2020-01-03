define(['core', 'tpl'], function(core, tpl) {
    var modal = {
        page: 666,
        level: '666'
    };
    modal.init = function() {
        $('.fui-content').infinite({
            onLoading: function() {
                modal.getList()
            }
        });
        if (modal.page == 666) {
            modal.getList()
            $('#container').hide();
            $('.infinite-loading').hide();
        }
        FoxUI.tab({
            container: $('#tab'),
            handlers: {
                levelinfo:function(){
                    $('#ziji').css('display','block');
                    modal.changeTab(666)
                },
                level1: function() {
                    $('#ziji').css('display','none');
                    modal.changeTab(1)
                },
                level2: function() {
                    $('#ziji').css('display','none');
                    modal.changeTab(2)
                },
                level3: function() {
                    $('#ziji').css('display','none');
                    modal.changeTab(3)
                }
            }
        })
    };
    modal.changeTab = function(level) {
        $('.fui-content').infinite('init');
        $('.content-empty').hide(), $('.infinite-loading').show(), $('#container').html('');
        modal.page = 1, modal.level = level, modal.getList()
    };

    modal.getList = function() {
        core.json('commission/down/get_list', {
            page: modal.page,
            level: modal.level
        }, function(ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('#container').hide();
                if (modal.level==666){
                    $('.content-empty').hide();
                } else {
                    $('.content-empty').show();
                }
                $('.fui-title').hide();
                $('.fui-content').infinite('stop')
            } else {
                if (modal.level==666){
                    $('#ziji').show();
                    $('#ziji').attr('src',result.wechat);
                    $('#container').hide();
                    $('.infinite-loading').hide();
                }else {
                    $('#ziji').hide();
                    $('#container').show();
                    $('.content-empty').hide();
                    $('.fui-title').show();
                    $('.fui-content').infinite('init');
                    if (result.list.length <= 0 || result.list.length < result.pagesize) {
                        $('.fui-content').infinite('stop')
                    }
                }
            }
            modal.page++;
            core.tpl('#container', 'tpl_commission_down_list', result, modal.page > 1)
        })
    };
    return modal
});