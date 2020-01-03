define(["biz"], function(biz) {
    model = {};
    model.multi = false;
    model.callback = '';
    model.ele = {};
    model.listenPool = [1];
    model.selectedPool = {};
    model.merchid = 0;
    model.no_merchid = 0;
    model.url = function(routes, merch) {
        if (merch) {
            var url = './merchant.php?c=site&a=entry&m=ewei_shopv2&do=web&r=' + routes.replace(/\//ig, '.')
        } else {
            var url = './index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=' + routes.replace(/\//ig, '.')
        }
        return url
    };
    model.post_url = model.url('utiltwo.goods_selector', model.merchid);
    model.open = function(callback, type, merchid, multi, api_url, selected_ids, no_merchid, platform) {
        model.merchid = merchid;
        model.no_merchid = no_merchid;
        model.post_url = model.url('utiltwo.goods_selector', merchid);
        model.platform = platform;
        var elename = 'goods_selector';
        if (api_url && api_url.length > 0) {
            model.post_url = api_url
        }
        if (multi) {
            model.multi = true
        } else {
            model.multi = false
        } if (type == 'creditshop') {
            model.post_url += '&creditshop=1'
        } else if (type == 'group') {
            model.post_url += '&group=1'
        } else if (type == 'quick') {
            model.post_url += '&quick=1'
        } else if (type == 2) {
            model.post_url += '&pagetype=2'
        }
        if (merchid) {
            model.post_url += '&merchid=' + merchid
        }
        model.callback = callback;
        if (typeof(model.callback) == 'string') {
            var url = model.url('utiltwo.goods_selector.js', merchid);
            model.name = elename;
            $('#goods-selector-modal').remove();
            $('body').append('<div id="goods-selector-modal"></div>');
            $.ajax({
                url: url,
                async: false,
                success: function(htm) {
                    $('#goods-selector-modal').empty().html(htm);
                    return
                }
            });
            model.ele = $("#goods_selector_" + model.name);
            model.mask = model.ele.find(".modal");
            model.close1 = model.ele.find(".modal").find(".close");
            model.modal = model.ele.find(".modal").find(".modal-dialog");
            model.close2 = model.ele.find(".modal").find(".modal-footer").find(".btn");
            model.s2id_autogen1 = model.ele.find("#s2id_autogen1");
            model.select2drop = model.ele.find("#select2-drop");
            model.select2result = model.ele.find(".select2-result");
            model.select2chosen = model.ele.find("#select2-chosen-2");
            model.$search = model.ele.find(".search");
            model.$goodsgroup = 0;
            model.getpage(1);
            model.mask.css("display", "block");
            setTimeout("model.mask.addClass('in')");
            var json = model.ele.find("textarea[name=" + model.name + "]").html();
            if (model.isJSON(json)) {
                model.selectedPool = JSON.parse(json)
            } else {
                model.selectedPool = {};
                model.ele.find("textarea[name=" + model.name + "]").html("")
            }
            model.listen();
            if (selected_ids && selected_ids.length > 0) {
                $.each(selected_ids, function(i, v) {
                    model.selectedPool[v] = {
                        id: v
                    };
                    model.selectStatus()
                })
            }
        }
    };
    model.init = function() {
        var textareas = $(".goods-selector-textarea");
        $.each(textareas, function(i, v) {
            var obj = $(v);
            var json = obj.html();
            model.name = obj.attr("name");
            if (model.isJSON(json)) {
                model.selectedPool = JSON.parse(json)
            } else {
                model.selectedPool = {};
                obj.html("")
            }
            model.put_selected_to_list()
        });
        var gs = $('.goods-selector');
        $.each(gs, function(index, v) {
            var op_switch = $(this).data('switch');
            var hrefs = $(v).find('.goods-selector-op');
            $.each(hrefs, function(index, hrefobj) {
                var href = $(hrefobj).attr('href');
                href += ('&nooption=' + (op_switch == '0' ? '1' : '0'));
                $(hrefobj).attr('href', href)
            })
        });
        $(document).on("click", ".goods-selector-cancel", function() {
            model.name = $(this).parent().parent().parent().data("name");
            var id = $(this).data("id");
            model.del(id);
            model.ele = $("#goods_selector_" + model.name);
            var json = model.ele.find("textarea[name=" + model.name + "]").html();
            if (model.isJSON(json)) {
                model.selectedPool = JSON.parse(json)
            } else {
                model.selectedPool = {};
                model.ele.find("textarea[name=" + model.name + "]").html("")
            }
            delete model.selectedPool[id];
            model.saveSelected();
            model.name = model.ele = undefined
        });
        $(document).on("click", ".goods-selector-op", function() {
            model.name = $(this).parent().parent().parent().data("name");
            var name = model.name;
            model.ele = $("#goods_selector_" + model.name);
            model.option_switch = $(model.ele).attr("data-switch");
            var href = $(this).attr('href');
            href = href.replace('nooption=undefined', 'nooption=' + model.option_switch == 0 ? 1 : 0);
            href += '&nooption=' + (model.option_switch == 0 ? '1' : '0');
            $(this).attr('href', href);
            var goodsid = $(this).data("id");
            var thismodal = $("#goods-selector-opmodal-" + goodsid);
            var json = $("#goods_selector_" + name).find(".goods-selector-textarea").html();
            if (model.isJSON(json)) {
                model.selectedPool = JSON.parse(json)
            } else {
                model.selectedPool = {};
                model.ele.find("textarea[name=" + model.name + "]").html("")
            }
            var goods = model.selectedPool[goodsid]
        });
        $(document).on("click", ".goods-selector-op-option", function() {
            model.ele = $("#goods_selector_" + model.name);
            var goodsid = $(this).data("id");
            var checked = $("#goods-selector-opmodal-" + goodsid).find(".option-item:checked");
            var options = {};
            $.each(checked, function(i, v) {
                var thisobj = $(v);
                var input = thisobj.parent().parent().find("input").not(".option-item");
                var column = {};
                $.each(input, function(j, k) {
                    column[$(k).attr("name")] = $(k).val()
                });
                var obj = {
                    id: thisobj.val(),
                    marketprice: thisobj.data("price"),
                    title: thisobj.parent().parent().find("td:nth-child(1)").text(),
                    stock: thisobj.parent().parent().find("td:nth-child(3)").text(),
                    column: column
                };
                options[thisobj.val()] = obj
            });
            var json = model.ele.find("textarea[name=" + model.name + "]").html();
            if (model.isJSON(json)) {
                model.selectedPool = JSON.parse(json)
            } else {
                model.selectedPool = {};
                model.ele.find("textarea[name=" + model.name + "]").html("")
            }
            model.selectedPool[goodsid]["options"] = options;
            model.saveSelected();
            model.name = undefined
        });
        $(document).on("click", ".goods-selector-op-goods", function() {
            model.ele = $("#goods_selector_" + model.name);
            var goodsid = $(this).data("id");
            var json = model.ele.find("textarea[name=" + model.name + "]").html();
            if (model.isJSON(json)) {
                model.selectedPool = JSON.parse(json)
            } else {
                model.selectedPool = {};
                model.ele.find("textarea[name=" + model.name + "]").html("")
            }
            var column = {};
            var input = $("#goods-selector-opmodal-" + goodsid).find("input");
            $.each(input, function(j, k) {
                column[$(k).attr("name")] = $(k).val()
            });
            model.selectedPool[goodsid]["column"] = column;
            model.saveSelected()
        });
        model.p = $(".goods-selector-open");
        model.p.click(function() {
            var url = $(this).data('url');
            var type = $(this).data('type');
            if (type == 'creditshop') {
                model.post_url += '&creditshop=1'
            } else if (type == 'group') {
                model.post_url += '&group=1'
            }
            var merchid = $(this).data('merchid');
            if (merchid) {
                model.post_url += '&merchid=' + merchid
            }
            var elename = $(this).attr("data-name");
            model.callback = $(this).data('callback');
            if (model.callback) {
                var url = model.url('utiltwo.goods_selector.js', merchid);
                if (model.loaded) {} else {
                    $('body').append('<div id="goods-selector-modal"></div>');
                    $.ajax({
                        url: url,
                        async: false,
                        success: function(htm) {
                            $('#goods-selector-modal').empty().html(htm);
                            model.loaded = 1;
                            return
                        }
                    })
                }
            }
            model.name = elename;
            model.ele = $("#goods_selector_" + model.name);
            model.mask = model.ele.find(".modal");
            model.close1 = model.ele.find(".modal").find(".close");
            model.modal = model.ele.find(".modal").find(".modal-dialog");
            model.close2 = model.ele.find(".modal").find(".modal-footer").find(".btn");
            model.s2id_autogen1 = model.ele.find("#s2id_autogen1");
            model.select2drop = model.ele.find("#select2-drop");
            model.select2result = model.ele.find(".select2-result");
            model.select2chosen = model.ele.find("#select2-chosen-2");
            model.$search = model.ele.find(".search");
            model.$goodsgroup = 0;
            model.getpage(1);
            model.mask.css("display", "block");
            setTimeout("model.mask.addClass('in')");
            var json = model.ele.find("textarea[name=" + model.name + "]").html();
            if (model.isJSON(json)) {
                model.selectedPool = JSON.parse(json)
            } else {
                model.selectedPool = {};
                model.ele.find("textarea[name=" + model.name + "]").html("")
            } if (model.listenPool.indexOf(model.name) < 0) {
                model.listen()
            }
        })
    };
    model.listen = function() {
        $(document).keypress(function(e) {
            if (e.which == 13 && model.ele != undefined) {
                model.jumpnow(1);
                return false
            }
        });
        model.listenPool.push(model.name);
        $(model.modal).on("click", ".pager-nav", function() {
            var num = Number($(this).attr("page"));
            model.jumpnow(num)
        });
        $(model.modal).on("change", ".page-raduis", function() {
            var num = Number($(this).val());
            $(this).parent().next("li").find("a").attr("page", num)
        });
        //可以改为异步ajax2019-9-4
        $(model.modal).on("click", ".selectit", function() {
            var goods = {};
            goods = $(this).data("json");
            if (model.callback) {
                goods.act = 1;
                //商品业务类型
                $("select[name=goodsbusinesstype]").find("option[value="+goods.goodsbusinesstype+"]").attr("selected",true);
                $('#goodsname').val(goods.title);//商品标题
                $('#displayorder').val(goods.displayorder);//商品排序
                $('#unit').val(goods.unit);//商品单位
                $('#subtitle').val(goods.subtitle);//副标题
                $('input[name=keywords]').val(goods.keywords);//搜索关键词
                $('#marketprice').val(goods.marketprice); //商品价格
                $('#productprice').val(goods.productprice); //商品原价
                $('#sales').val(goods.sales);//已售出件数
                $('#total').val(goods.total);//库存
                if (goods.isnew==1){$('#isnew').attr("checked","checked");}//是否选中新品
                if (goods.issendfree==1){$('#issendfree').attr("checked","checked");}//是否选中包邮
                if (goods.thumb_first==1){$('#thumb_first').attr("checked","checked");}//是否选中详情显示首图
                if (goods.type==2 || goods.type==2==3){$('.form-group.dispatch_info').attr("display","block")}//运费模板、统一邮费是否显示
                if (goods.dispatchtype){$('input[name=dispatchtype]').attr("checked","checked");}//运费模板（还有小问题）
                if (goods.cash!=0) {$('input[name=cash]').attr("checked","checked");}//货到付款
                if (goods.quality!=0) {$('input[name=quality]').attr("checked","checked");}//正品保证
                if (goods.seven!=0) {$('input[name=seven]').attr("checked","checked");}//7天无理由退换
                if (goods.invoice!=0) {$('input[name=invoice]').attr("checked","checked");}//发票
                if (goods.repair!=0) {$('input[name=repair]').attr("checked","checked");}//保修
                if (goods.status!=0) {$('input[name=status]').attr("checked","checked");}//保修
                //商品详情
                require(['ueditor'], function(ueditor){
                    var ue = ueditor.getEditor('content');
                     ue.setContent(goods.content);
                });
                //启用商品规格
                // if (goods.hasoption==1){
                //     $('#hasoption').attr("checked","checked");
                //     $('#goodssn').attr('readonly',true);
                //     $('#productsn').attr('readonly',true);
                //     $('#weight').attr('readonly',true);
                //     $('#total').attr('readonly',true);
                //
                //     $("#tboption").show();
                //     $("#tbdiscount").show();
                //     // $("#specs").prepend();
                //     // $("#isdiscount_discounts").show();
                //     // $("#isdiscount_discounts_default").hide();
                //     // $("#commission").show();
                //     // $("#commission_default").hide();
                //     // $("#discounts_type1").show().parent().show();
                // }
                var  json_cates=goods.cates,html='',html2='';
                //商品分类
                $.each(json_cates, function(n,value){
                    $("#cates option[value="+n+"]").attr('selected',true)
                    html+= '<li class="select2-search-choice"> <div>'+value+'</div> <a href="#" class="select2-search-choice-close" tabindex="-1"></a> </li>';
                });
                $(".select2-choices").prepend(html);
                //商品图片
                var json_tupian=goods.thumb_url;
                $.each(json_tupian, function(n,value){
                    html2+='<div class="multi-item">';
                    html2+='<img onerror="this.src=\'../addons/ewei_shopv2/static/images/nopic.png\'; this.title=\'图片未找到.\'" src="http://qiniu.xmylyg.com/'+value+'" class="img-responsive img-thumbnail">';
                    html2+='<input type="hidden" name="thumbs[]" value="'+value+'">';
                    html2+='<em class="close" title="删除这张图片" onclick="deleteMultiImage(this)">×</em>';
                    html2+= '</div>';
                });
                $(".gimgs .input-group.multi-img-details.ui-sortable").prepend(html2);
                $('.modal.in').find('.close').trigger('click')//关闭选择器

            }

        });

        $(model.modal).on("click", ".cancelit", function() {
            var goods = {};
            goods = $(this).data("json");
            delete model.selectedPool[goods.id];
            if (model.multi) {
                goods.act = 0;
                eval(model.callback + "(goods)")
            }
            $(this).removeClass("cancelit").removeClass("label-danger").addClass("selectit").addClass("label-primary").text("选择");
            model.del(goods.id)
        });
        $(model.mask).click(function() {
            model.mask.removeClass("in");
            setTimeout("model.mask.css('display','none');", 150);
            model.s2id_autogen1.css("border", "1px solid #efefef");
            model.select2drop.hide();
            model.saveSelected()
        });
        $(model.modal).click(function(event) {
            var e = window.event || event;
            if (e.stopPropagation) {
                e.stopPropagation()
            } else {
                e.cancelBubble = true
            }
            model.select2drop.hide();
            model.mask.css("display", "block");
            model.s2id_autogen1.css("border", "1px solid #efefef")
        });
        $(model.close1).click(function(event) {
            var e = window.event || event;
            if (e.stopPropagation) {
                e.stopPropagation()
            } else {
                e.cancelBubble = true
            }
            model.mask.removeClass("in");
            setTimeout("model.mask.css('display','none');", 150);
            model.saveSelected()
        });
        $(model.close2).click(function(event) {
            var e = window.event || event;
            if (e.stopPropagation) {
                e.stopPropagation()
            } else {
                e.cancelBubble = true
            }
            model.mask.removeClass("in");
            setTimeout("model.mask.css('display','none');", 150);
            model.saveSelected()
        });
        $(model.s2id_autogen1).click(function() {
            var e = window.event || event;
            if (e.stopPropagation) {
                e.stopPropagation()
            } else {
                e.cancelBubble = true
            }
            model.select2drop.show();
            $(this).css("border", "1px solid #44abf7 ");
            $(this).css("border-bottom", "0")
        });
        $(model.select2drop).click(function() {
            var e = window.event || event;
            if (e.stopPropagation) {
                e.stopPropagation()
            } else {
                e.cancelBubble = true
            }
            model.select2drop.show()
        });
        model.ele.find('.fenlei').find('select').change(function() {
            var e = window.event || event;
            if (e.stopPropagation) {
                e.stopPropagation()
            } else {
                e.cancelBubble = true
            }
            model.select2drop.hide();
            model.goodsgroup = $(this).val();
            model.select2chosen.html($(this).find("div").html());
            model.s2id_autogen1.css("border", "1px solid #efefef")
        });
        $(model.select2result).hover(function() {
            $(this).addClass("select2-highlighted")
        }, function() {
            $(this).removeClass("select2-highlighted")
        })
    };
    model.put_selected_to_list = function() {
        $.each(model.selectedPool, function(i, v) {
            model.put(v.id)
        });
        model.selectedPool = {};
        model.name = undefined
    };
    model.put = function(id) {
        model.option_switch = $(model.ele).data("switch");
        var url = "./index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=utiltwo.goods_selector.op&id=" + id + "&column=" + encodeURI($("#goods-selected-list-" + model.name).attr("data-column"));
        if (model.option_switch == 0) {
            url += "&nooption=1"
        }
        var set_color = "btn-danger";
        var htm = '<tr id="goods-selected-goods' + id + '">                <td><img src="' + model.selectedPool[id].thumb + '" style="width: 40px;height: 40px;border: solid #ccc 1px"></td>                <td><p class="title">' + model.selectedPool[id].title + '</p>                <p class="text text-danger">¥' + model.selectedPool[id].marketprice + "</p></td>                <td>" + '</td>                <td><a data-toggle="ajaxModal" href="' + url + '" class="btn ' + set_color + ' btn-sm goods-selector-op" data-id="' + model.selectedPool[id].id + '">商品设置</a>                <a class="btn btn-default btn-sm goods-selector-cancel" data-id="' + id + '">取消</a></td>                </tr>';
        $("#goods-selected-list-" + model.name).append(htm)
    };
    model.del = function(id) {
        $("#goods-selected-list-" + model.name).find("#goods-selected-goods" + id).remove()
    };
    model.selectStatus = function() {
        var selectBtn = model.ele.find(".selectit");
        $.each(selectBtn, function(i, v) {
            var obj = $(v);
            var thisid = obj.data("id");
            if (model.selectedPool[thisid] !== undefined) {
                obj.removeClass("selectit").removeClass("label-primary").addClass("cancelit").addClass("label-danger").text("取消")
            }
        })
    };
    model.isJSON = function(str) {
        if (typeof str == "string") {
            try {
                var obj = JSON.parse(str);
                if (str.indexOf("{") > -1) {
                    return true
                } else {
                    return false
                }
            } catch (e) {
                console.log("隐藏域数据格式不能解析，已清空，请重新选择：" + e);
                return false
            }
        }
        return false
    };
    model.saveSelected = function() {
        model.ele.find("textarea[name=" + model.name + "]").html(JSON.stringify(model.selectedPool));
        model.selectedPool = {}
    };
    model.jumpnow = function(page) {
        model.keyword = model.$search.val();
        model.getpage(page, model.keyword, model.goodsgroup)
    };
    model.getpage = function(page, keywords, goodsgroup) {
        if (!page > 0) {
            page = 1
        }
        if (keywords == undefined) {
            model.$search.val("")
        }
        var condition = model.ele.find("where").text();
        $.ajax({
            url: model.post_url,
            type: "post",
            data: {
                data: {},
                page: page,
                keywords: keywords,
                goodsgroup: goodsgroup,
                condition: condition,
                no_merchid: model.no_merchid,
                platform: model.platform,
            },
            success: function(htm) {
                model.ele.find(".content").empty().html(htm);
                model.selectStatus()
            },
        })
    };

    return model
});