$.fn.hasAttr = function(name) {
   return this.attr(name) !== undefined;
};

$(function(){
	//ajaxSetup
	$.ajaxSetup({
		url:'ajax/ajax.php',
		type:'POST',
		dataType: 'json',
		beforeSend: function(){
			$('body').append('<div class="loading"></>');
		},
		complete: function(){
			$('.loading').remove();
		},
		cache: false,
		error: function(request, status, error){
			alert(error);
		}
	});

	$(document).on("click", ".archive", function(){
		if ($('.systemMessage').length) $('.systemMessage').remove();
		if (!$('.systemMessage').length) $('body').prepend("<div class='systemMessage'><a class='close'>x</a></div>");
		$('.systemMessage').append("<div class='archive_process'>Copying data to archive. Please wait and don`t close browser... </div>");
		archive();
		function archive(){
			data={'archive':true};
			$.ajax({
				data:data,
				success: function(data){
					var maxSteps = 10;
					if (!$(".archive").hasAttr('count') || $(".archive").attr('count')==maxSteps) $(".archive").attr('count',0)
					var count = +($(".archive").attr('count'));
					$(".archive").attr('count',count+1)
					$(".systemMessage").append("<div class='archive_process'>"+data.period+" processed... </div>");
					if ($(".archive").attr('count')==maxSteps) return;
					else if (data.startOver){
						$(".archive_process:last").append('Starting over');
						archive();
					} else $(".archive_process:last").append('Finished');
				}
			});//.ajax			
		}
	});

	$(document).on("click", "[data-process]", function(){
		var substat = $(this).closest('.statistics').hasClass('details')?true:false;
		var process = $(this).attr("data-process");
		if ($(this).closest('.details').attr("data-processes")!==undefined) var processes = ':::'+$(this).closest('.details').attr("data-processes");
		else var processes = '';
		var data = {};
		data['process'] = process+'::'+$(this).closest("tr").attr("id")+processes;
		if (substat){
			data['substat'] = $(this).closest('tr').find('a').attr('href');
		}
		if ($('#errors').is(':checked')) data['errors'] = true;
		if (!substat){
			if ($('.systemMessage').length) $('.systemMessage').remove();
			if (!$('.systemMessage').length) $('body').append("<div class='systemMessage'><a class='close'>x</a></div>");
		}
			
		$.ajax({
			data:data,
			dataType: 'json',
			success: function(data){
				if (!substat) $(".systemMessage").append("<div class='statsDetails'><span>"+data.result+"</span></div>");
				else {
					$('body').append('<div class="systemMessage substat"><a class="close">x</a>'+data.result+'</div>');
				}
			}
		});
	});

/*	$(document).on("click", "[data-process]", function(){
		var substat = $(this).closest('.statistics').hasClass('details')?true:false;
		var process = $(this).attr("data-process");
		var id = $(this).closest("tr").attr("id");
		var data = {};
		if (substat){
			data[process] = $(this).closest('tr').find('a').attr('href');
			data['substat'] = true;
		} else {
			data[process] = id;
		}
		if ($('#errors').is(':checked')) data['errors'] = true;
		if (!substat){
			if ($('.systemMessage').length) $('.systemMessage').remove();
			if (!$('.systemMessage').length) $('body').prepend("<div class='systemMessage'><a class='close'>x</a></div>");
		}
			
		$.ajax({
			data:data,
			dataType: 'json',
			success: function(data){
				if (!substat) $(".systemMessage").append("<div class='statsDetails'><span>"+data.result+"</span></div>");
			}
		});
	});*/

	$(document).on("click", "[data-trade]", function(){
		var href = $(this);
		var process = $(this).attr("data-trade");
		var id = $(this).closest("tr").attr("id");
		var data = {};
		data[process] = id;
		if (process=='trade-add'){
			data['anchor'] = prompt('Anchor');
			data['url'] = prompt('Trade url');
		} else if (process=='trade-add-new'){
			data['trade-add'] = prompt('Domain (no www)');
			data['anchor'] = prompt('Anchor');
			data['url'] = prompt('Trade url');
		} else if (process=='trade-edit'){
			data['anchor'] = prompt('Anchor',$(this).attr("data-anchor"));
			data['url'] = prompt('Trade url',$(this).attr("data-url"));
		}
					
		$.ajax({
			data:data,
			dataType: 'json',
			success: function(data){
				if (process=='trade-add') href.after(" Added");
				else if (process=='trade-remove') href.after(" Removed");
				else if (process=='trade-edit') href.after(" Edited");
			}
		});//.ajax
	});

	$(document).on("click", "[name='rebuild'],.preSet,#statsError", function(){
		var data = {};
		var date = new Date();
		var yesterday = new Date(date.getFullYear(), date.getMonth(), date.getDate()-1);
		var monthFirstDay = new Date(date.getFullYear(), date.getMonth(), 1);
		var yearFirstDay = new Date(date.getFullYear(), 1, 1);
		
		if ($('.statistics').hasClass('netsites')) data['build-stats'] = 'network';
		else data['build-stats'] = 'site';
		
		if ($(this).hasClass('preSet')){
			data['dateStart'] = date.format("yyyy-mm-dd");
			data['dateEnd'] = date.format("yyyy-mm-dd");
			if ($(this).attr('id')=='statsMonth') data['dateStart'] = monthFirstDay.format("yyyy-mm-dd");
			else if ($(this).attr('id')=='statsYear') data['dateStart'] = yearFirstDay.format("yyyy-mm-dd");
			else if ($(this).attr('id')=='statsYesterday'){
				data['dateStart'] = yesterday.format("yyyy-mm-dd");
				data['dateEnd'] = yesterday.format("yyyy-mm-dd");
			}
		} else if ($(this).hasClass('statsError')){
			data['statsError'] = true;
		} else {
			data['dateStart'] = $("[name='dateStart']").val();
			data['dateEnd'] = $("[name='dateEnd']").val();
		}
		$.ajax({
			data:data,
			dataType: 'json',
			success: function(data){
				$(".statistics").remove();
				$(".calenderForm").after(data.result);
			}
		});
	});

	$(document).on('click', '[data-mailing]', function(){
		var process = $(this).attr('data-mailing');
		var data = {};
		data[process] = '';
		$.ajax({
			data:data,
			dataType: 'json',
			success: function(data){
				if (process=='mailing'){
					$(".statistics").remove();
					$(".calenderForm").after(data.result);
					$('.mailing tr').each(function(){
						$(this).find('a').attr('href','https://www.exposedontape.xxx'+$(this).find('a').attr('href'));
						$(this).find('a').attr('target','_blank');
						$(this).find('img').attr('src','https://www.exposedontape.xxx'+$(this).find('img').attr('src'));	
					});
				}
			}
		});
	});

	$(document).on('click', '[name="send"]', function(){
		var sets = [];
		$('.mailing tr').each(function(){
            var checkbox = $(this).find('[type="checkbox"]');			
			if (checkbox.is(':checked')){
				var wraped = $(this).find('.new');
				if (wraped.length==0) $(this).find('a').wrapAll("<div class='new'></div>");
				sets.push('<div class="video">'+$(this).find('.new').html()+'</div>');
			}
        });
		var data = {};
		data['mailing'] = sets.join('');
		$.ajax({
			data:data,
			dataType: 'json',
			success: function(data){
				alert('Sending started');
			}
		});
	});

	$(document).on("click", "[data-network]", function(){
		var clicked = $(this);
		var process = $(this).attr('data-network');
		var data = {};
		data[process] = true;
		if (process=='network-upload' || process=='guardian-upload') data['id'] = $(this).closest('tr').attr('id');
		$.ajax({
			data:data,
			dataType: 'json',
			success: function(data){
				if (process=='network-upload' || process=='guardian-upload'){
					clicked.after(' '+data.result);
				} else {
					$('.statistics').remove();
					$(".calenderForm").after(data.result);
				}
			}
		});//.ajax
	});
	
	$(document).on("click", ".close", function(){
		$(this).closest('div').fadeOut();
	});
	
	$('.calender').datepick({dateFormat: 'yyyy-mm-dd'});
	
	$(document).mouseup(function (e) {
		var container = $(".systemMessage");
		if (!container.is(e.target)
			&& container.has(e.target).length === 0)
		{
			container.fadeOut();
		}
	});
	
	
});

/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */

var dateFormat = function () {
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) throw SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
};

