var $cc = {};
$cc.expiry = function(e){
  if (e.key != 'Backspace'){
	var number = String(this.value);

	var cleanNumber = '';
	for (var i = 0; i<number.length; i++){
	  if (i == 1 && number.charAt(i) == '/'){
		cleanNumber = 0 + number.charAt(0);
	  }
	  if (/^[0-9]+$/.test(number.charAt(i))){
		cleanNumber += number.charAt(i);
	  }
	}

	var formattedMonth = ''
	for (var i = 0; i<cleanNumber.length; i++){
	  if (/^[0-9]+$/.test(cleanNumber.charAt(i))){
		if (i == 0 && cleanNumber.charAt(i) > 1){
		  formattedMonth += 0;
		  formattedMonth += cleanNumber.charAt(i);
		  formattedMonth += ' / ';
		}
		else if (i == 1){
		  formattedMonth += cleanNumber.charAt(i);
		  formattedMonth += ' / ';
		}else{
		  formattedMonth += cleanNumber.charAt(i);
		}
	  }
	}
	this.value = formattedMonth;
  }
  
  //validate
  var valid = true;
  var a, b, c, d, e, f;
  a = this.value
  a = a.replace(/\s/g, "");
  e = a.split("/", 2);
  b = e[0];
  if (null != e[1]) {
  	d = e[1];
  }
  if (b > 12) {
  	valid = false;
  } else if (null != d) {
	  f = new Date().getFullYear().toString().substr(-2);
	  if (d < f) {
		valid = false;
	  }
  }
  if (!valid) {
  	jQuery('#payme-card-expiry').addClass('payme-input-error');
  } else {
  	jQuery('#payme-card-expiry').removeClass('payme-input-error');
  }
}

$cc.validate = function(e){
	var number = String(e.target.value);
	var cleanNumber = '';
	for (var i = 0; i<number.length; i++){
		if (/^[0-9]+$/.test(number.charAt(i))){
			cleanNumber += number.charAt(i);
		}
	}

	if (e.key != 'Backspace'){
		var formatNumber = '';
		for (var i = 0; i<cleanNumber.length; i++){
			if (i == 3 || i == 7 || i == 11 ){
				formatNumber = formatNumber + cleanNumber.charAt(i) + ' '
			} else{
				formatNumber += cleanNumber.charAt(i)
			}
		}
		e.target.value = formatNumber;
	}

	if (cleanNumber.length >= 12){
		var isLuhn = luhn(cleanNumber);
	}

	function luhn(number){
		var numberArray = number.split('').reverse();
		for (var i=0; i<numberArray.length; i++) {
			if (i%2 != 0) {
				numberArray[i] = numberArray[i] * 2;
				if (numberArray[i] > 9){
					numberArray[i] = parseInt(String(numberArray[i]).charAt(0)) + parseInt(String(numberArray[i]).charAt(1))
				}
			}
		}
		
		var sum = 0;
		for (var i=1; i<numberArray.length; i++){
			sum += parseInt(numberArray[i]);
		}
		sum = sum * 9 % 10;
		
		if (numberArray[0] == sum){
			return true
		} else {
			return false
		}
	}
	
	if (isLuhn == true){
		jQuery('#payme-card-number').removeClass('payme-input-error');
	} else{
		jQuery('#payme-card-number').addClass('payme-input-error');
	}

	var card_types = [
		{
		  name: 'amex',
		  pattern: /^3[47]/,
		  valid_length: [15]
		}, {
		  name: 'diners',
		  pattern: /^(36|38|30[0-5])/,
		  valid_length: [14]
		}, {
		  name: 'jcb',
		  pattern: /^35(2[89]|[3-8][0-9])/,
		  valid_length: [16]
		}, {
		  name: 'laser',
		  pattern: /^(6304|670[69]|6771)/,
		  valid_length: [16, 17, 18, 19]
		}, {
		  name: 'visa_electron',
		  pattern: /^(4026|417500|4508|4844|491(3|7))/,
		  valid_length: [16]
		}, {
		  name: 'visa',
		  pattern: /^4/,
		  valid_length: [16]
		}, {
		  name: 'mastercard',
		  pattern: /^5[1-5]/,
		  valid_length: [16]
		}, {
		  name: 'maestro',
		  pattern: /^(5018|5020|5038|6304|6759|676[1-3])/,
		  valid_length: [12, 13, 14, 15, 16, 17, 18, 19]
		}, {
		  name: 'discover',
		  pattern: /^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)/,
		  valid_length: [16]
		}, {
		  name: 'unionpay',
		  pattern: /^62/,
		  valid_length: [16, 17, 18, 19]
		}, {
		  name: 'forbrugsforeningen',
		  pattern: /^600/,
		  valid_length: [16]
		}, {
		  name: 'dankort',
		  pattern: /^5019/,
		  valid_length: [16]
		}
	];

	var valid = false;
	for (var i = 0; i< card_types.length; i++){
		if (number.match(card_types[i].pattern)){
			jQuery('#payme-card-number').css('background-image','url('+payme_plugin_url+'assets/images/cards/svg/'+card_types[i].name+'.svg)');
			
			valid = true;
			break;
		}
	}
	
	if (!valid) {
		jQuery('#payme-card-number').css('background-image','none');
		jQuery('#payme-card-number').addClass('payme-input-error');
	}
}

$cc.cvc = function(e){
	var number = String(this.value);

	var cleanNumber = '';
	for (var i = 0; i<number.length; i++){
	  if (i == 1 && number.charAt(i) == '/'){
		cleanNumber = 0 + number.charAt(0);
	  }
	  if (/^[0-9]+$/.test(number.charAt(i))){
		cleanNumber += number.charAt(i);
	  }
	}
	e.target.value = cleanNumber;

  var valid = false;
  if(cleanNumber.length == 3 || cleanNumber.length == 4) {
	  valid = true;
  }
  if (!valid) {
  	jQuery('#payme-card-cvc').addClass('payme-input-error');
  } else {
  	jQuery('#payme-card-cvc').removeClass('payme-input-error');
  }
}

$cc.social = function(e){
	var number = String(this.value);

	var cleanNumber = '';
	for (var i = 0; i<number.length; i++){
	  if (i == 1 && number.charAt(i) == '/'){
		cleanNumber = 0 + number.charAt(0);
	  }
	  if (/^[0-9]+$/.test(number.charAt(i))){
		cleanNumber += number.charAt(i);
	  }
	}
	e.target.value = cleanNumber;

  var valid = false;
  if(cleanNumber.length >= 5 && cleanNumber.length <= 9) {
	  valid = true;
  }

  if (!valid) {
  	jQuery('#payme-social-id').addClass('payme-input-error');
  } else {
  	jQuery('#payme-social-id').removeClass('payme-input-error');
  }
}

jQuery(document).ready(function(e) {
	jQuery('#payme-card-number').bind('blur', function(e){
		if (jQuery(this).val() == '') {
			jQuery('#payme-card-number').addClass('payme-input-error');
		}
	});
	jQuery('#payme-card-expiry').bind('blur', function(e){
		if (jQuery(this).val() == '') {
			jQuery('#payme-card-expiry').addClass('payme-input-error');
		}
	});
	jQuery('#payme-card-cvc').bind('blur', function(e){
		if (jQuery(this).val() == '') {
			jQuery('#payme-card-cvc').addClass('payme-input-error');
		}
	});
	jQuery('#payme-social-id').bind('blur', function(e){
		if (jQuery(this).val() == '') {
			jQuery('#payme-social-id').addClass('payme-input-error');
		}
	});
});

/*function(a) {
            if (a = String(a), a.length > 9 || a.length < 5) return !1;
            if (isNaN(a)) return !1;
            if (a.length < 9)
                for (; a.length < 9;) a = "0" + a;
            for (var b, c = 0, d = 0; 9 > d; d++) b = Number(a.charAt(d)), b *= d % 2 + 1, b > 9 && (b -= 9), c += b;
            return c % 10 == 0
        },*/
