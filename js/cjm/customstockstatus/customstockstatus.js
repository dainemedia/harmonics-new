document.observe("dom:loaded", function() {
	if(spConfig.config.dynamics == 1 && spConfig.config.showbottom == 1){
		$$('.product-options-bottom')[0].insert({top: new Element('p', {id:'bottom-avail', class:'availability'}).update($$('p.availability')[0].innerHTML)});
		if(spConfig.config.showship == 1){
			$('bottom-avail').insert({after: new Element('p', {id:'bottom-shipsin', class:'shipsin'}).update($$('p.shipsin')[0].innerHTML)}); }
	}
});

Product.Config.prototype.getMatchingSimpleProduct = function(){
    var inScopeProductIds = this.getInScopeProductIds();
    if ((typeof inScopeProductIds != 'undefined') && (inScopeProductIds.length == 1)) {
        return inScopeProductIds[0];
    }
    return false;
};

Product.Config.prototype.getInScopeProductIds = function(optionalAllowedProducts) {

    var childProducts = this.config.childProducts;
    var allowedProducts = [];

    if ((typeof optionalAllowedProducts != 'undefined') && (optionalAllowedProducts.length > 0)) {
        allowedProducts = optionalAllowedProducts;
    }

    for(var s=0, len=this.settings.length-1; s<=len; s++) {
        if (this.settings[s].selectedIndex <= 0){
            break;
        }
        var selected = this.settings[s].options[this.settings[s].selectedIndex];
        if (s==0 && allowedProducts.length == 0){
            allowedProducts = selected.config.allowedProducts;
        } else {
            allowedProducts = allowedProducts.intersect(selected.config.allowedProducts).uniq();
        }
    }
    
    if ((typeof allowedProducts == 'undefined') || (allowedProducts.length == 0)) {
        productIds = Object.keys(childProducts);
    } else {
        productIds = allowedProducts;
    }
    return productIds;
};

Product.Config.prototype._getOptionLabel_base = Product.Config.prototype.getOptionLabel;
Product.Config.prototype.getOptionLabel = function(option, price) {
	
	//Get the parent methods string
	var str = Product.Config.prototype._getOptionLabel_base.call(this, option, price);
        
	//Run the updateStockStatus function if dynamics is enabled
   	if(this.config.dynamics == 1){
   		var childProductId = this.getMatchingSimpleProduct();
		childProductId ? this.updateStockStatus(childProductId) : this.updateStockStatus(false);
	}
        
  	//If we are on the last attribute, add the stock status to the string
   	option.allowedProducts.size() == 1 ? str+= this.config.childProducts[option.allowedProducts].configstockstatus : 0;
        
 	return str;
};

Product.Config.prototype.updateStockStatus = function(productId) {
    
    //Hide or show the product alerts
    if(productId && this.config.childProducts[productId].alert) {
		if(!$('product-'+productId+'-alert')){
			var alertLink = new Element('a', {href:this.config.childProducts[productId].alert, class:'out-of-stock-alert-links', id:'product-'+productId+'-alert'}),
				alertLinkTwo = new Element('a', {href:this.config.childProducts[productId].alert, class:'out-of-stock-alert-links', id:'product-'+productId+'-alert'});
			alertLink.update('Sign up to be notified when this product is back in-stock!');
			alertLinkTwo.update('Sign up to be notified when this product is back in-stock!');
			$$('.product-options-bottom')[0].insert({bottom: alertLink});
			alertLink.wrap(new Element('p', {class:'alert-link'}));
			$$('.availability')[0].insert({after: alertLinkTwo});
			$$('.add-to-cart').invoke('setStyle', ({ 'display': 'none' }))
		}
	} else {
		$$('a.out-of-stock-alert-links').invoke('remove');
		$$('.add-to-cart').invoke('setStyle', ({ 'display': 'block' }));
	}
	
	//Set ship date
	if(this.config.showship == 1){
		var ship = this.config.mainship;
		
		//If we are on the last item, set the ship text
  		if(productId) {
        	ship = this.config.childProducts[productId].shipsby; }
        
   	 	$$('.shipsin').invoke('update', ship);
	}
    
    //Set the main stock message
    var status = this.config.mainstock;
    
    //If we are on the last item and theres a stock status, set it
    if(productId && this.config.childProducts[productId].stockstatus) {
        status = this.config.childProducts[productId].stockstatus; }

    if (!status) { return; }

    $$('.availability').invoke('update', status);
};
