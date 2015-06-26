
var dpdLocator;


function dpdChosenShop(shopID) {
	var query = $.ajax({
		type: 'POST'
		,cache: false
		,url: 'index.php?route=checkout/dpd_carrier'
		,data: {
			confirmShop : shopID
		}
		,dataType: 'json'
		,success: function(json) {
			if(json.hasErrors){
				alert(json.errors);
			} else {
				$("#chosenShop").html(json.result);
				dpdLocator.hideLocator();
			}
		}
		,error: function(jqXHR, textStatus, errorThrown) {
			alert(textStatus + ": " + errorThrown);
		}
	});
}

function getShippingAddress(){
	if($('#shipping-address input[type=\'radio\'][checked=\'checked\']').length > 0) {
		switch ($('#shipping-address input[type=\'radio\'][checked=\'checked\']').val()) {
			case 'existing':
				var fullAddress = $('#shipping-existing option[selected=\'selected\']').html();
				var addressArray = fullAddress.split(",");
				addressArray.shift();
				return addressArray.join(',');
				break;
			case 'new':
				var address = "";
				$('#shipping-new input[type=\'text\']').each(function(index, obj){
					if(address != "") {
						address += ", ";
					}
					address += $(obj).val();
				});
				return address;
				break;
		}
	} else {
		var address = "";
		if($('#shipping-address input[type=\'text\']').length > 0){
			fields = $('#shipping-address input[type=\'text\']');
		} else {
			fields = $('#payment-address input[type=\'text\']');
		}
		$(fields).each(function(index, obj){
			if(obj.name == 'address_1'
				|| obj.name == 'city'
				|| obj.name == 'postcode') {
					if(address != "") {
						address += ", ";
					}
					address += $(obj).val();
				}
		});
		return address;
	}
}

function showLocator(target) {
	if($("#dpdLocatorContainer").length > 0){
		dpdLocator.showLocator();
	} else {
		//$(target).parents( "tr" ).first().prepend("<div id='dpdLocatorContainer'><div id='chosenShop'></div></div>");
		$(target).parents("tr").after("<tr><td colspan=\"3\"><div id='dpdLocatorContainer'><div id='chosenShop'></div></div></td></tr>");

		dpdLocator = new DPD.locator({
			imgpath: 'catalog/view/theme/default/image/DPD'
			,ajaxpath: 'index.php?route=checkout/dpd_carrier'
			,containerId: 'dpdLocatorContainer'
			,fullscreen: false
			,width: '100%'
			,height: '600px'
			,filter: 'pick-up'
			,callback: 'dpdChosenShop'
			,dictionaryXML: 'catalog/view/javascript/DPD/dictionary.xml'
			,language: 'en_US'
		});
		
		dpdLocator.initialize();

		dpdLocator.showLocator(getShippingAddress());
	}
}

function dpdCarrierLoad(e) {
	if(typeof(e.target.parentElement.attributes['for']) != "undefined"
		&& e.target.parentElement.attributes['for'].value == "dpd_carrier.pickup"
		&& document.getElementById('dpd_carrier.pickup').checked)
	{
		showLocator(document.getElementById('dpd_carrier.pickup'));
	}
}


$( document ).ready(function() {
	$( "body" ).on("click", "input[name='shipping_method']", function(e){
		if(e.target.id == "dpd_carrier.pickup") {
			showLocator(e.target);
		}
	});
	$( "body" ).on("click", "#button-shipping-method", function(e){
		if(document.getElementById('dpd_carrier.pickup').checked
			&& $("#chosenShop").html() == '') {
			alert('You can not continue without selecting a Pickup point.');
			$('html, body').animate({
				scrollTop: $("#dpdLocatorContainer").offset().top
			}, 2000);
			return false;
		}
	});
});