function updateQuantity(id, value) {
    var udmask = YAHOO.util.Dom.get('udq-'+id);
    YAHOO.util.Dom.setStyle(udmask, 'opacity', '0.75');
    YAHOO.util.Dom.removeClass(udmask, 'hide');
    
    var url = eXp.URL_FULL + 'index.php?ajax_action=1&controller=cart&action=updateQuantity&id='+id+'&value='+value;
    YAHOO.util.Connect.asyncRequest('GET', url, updateCart);
}

var updateCart = {
    success: function(o) {
        // little YUI 3 usage
        YUI().use("node",function(Y){
            
            if (typeof(EXPONENT.onQuantityAdjusted)==="undefined") {
                EXPONENT.onQuantityAdjusted = new YAHOO.util.CustomEvent('Quantity Adjusted',this,false,false, YAHOO.util.CustomEvent.FLAT);
            }
            EXPONENT.onQuantityAdjusted.fire();
        
        
            var cart_totals = YAHOO.util.Dom.getElementsByClassName('carttotal');
        
            var totals = YAHOO.lang.JSON.parse(o.responseText);
            var item_qty = YAHOO.util.Dom.getElementsByClassName('storeitem-' + totals.item_id);
            var item_ticker =  YAHOO.util.Dom.get('quantity-' + totals.item_id);
            item_ticker.value = totals.quantity;
            var item_td =  YAHOO.util.Dom.get('price-' + totals.item_id);
            var msgque =  Y.Node.get('#msg-queue');
            if (typeof(totals.message)!="undefined") {
                var msg =  Y.Node.get('#msg-queue #msg'+totals.item_id);
                //console.debug(msg);
                if (Y.Lang.isNull(msg)) {
                    msgque.appendChild(Y.Node.create('<ul id="msg'+totals.item_id+'" class="queue message"><li>'+totals.message+'</li></ul>'));
                }else {
                    msg.query('li').set('innerHTML',totals.message);
                };
            } else {
                var msg =  Y.Node.get('#msg-queue #msg'+totals.item_id);
                //console.debug(msg);
                if (!Y.Lang.isNull(msg)) {
                    msgque.removeChild(msg);
                };
                
            };
            for (var i=0; i<item_qty.length; i++){
                item_qty[i].innerHTML = totals.quantity;
            }
            for (var i=0; i<cart_totals.length; i++){
                cart_totals[i].innerHTML = totals.cart_total;
            }
            item_td.innerHTML = totals.item_total;
            
            //hide the mask again
            Y.get('#udq-quantity-'+totals.item_id).addClass('hide');
            
        });
    }
}

