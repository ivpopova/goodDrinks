/* GoodDrinks - Ivana Popova and Kiril Golov (C) 2021 */

jQuery(document).ready(function($) {
var $root = $('html, body');

$('ul.topmenu > li.drop').hover(
	function() {
		$(this).children('ul.dropmenu').addClass("hover");
	},
	function() {
		$(this).children('ul.dropmenu').removeClass("hover");
	}
);
$('ul.topmenu > li.drop').on("tap taphold", function(){
	$(this).children('ul.dropmenu').toggleClass("hover");
});

// show scroll to top button if needed
$(function() {
	$(window).scroll(function() {
		var scrollTop = $(document).scrollTop();
		if (scrollTop >= 220) {
			$('div#scroll').attr('class', 'scroll-to-top show');
		} else {
			$('div#scroll').attr('class', 'scroll-to-top');
		}
	});
});

// roll page to top if scroll button is clicked
$(function() {
	$('div#scroll').click(function() {
		$root.animate({ scrollTop: 0 }, 'fast');
	});
});

// initialise slider
$('.my-slider').unslider( {
	autoplay: true,
	infinite: true,
	speed: 900,
	delay: 9600 }
);
/* initialize swipe support for slider*/
$('.my-slider').unslider('initSwipe');

/* combine menu functionality with slider */
$(function() {
	$('ul.topmenu > li').hover( function() {
		$('div#index-page > div.unslider').css('z-index', '-1');
	},
	function() {
		// on mouseout, reset
		$('div#index-page > div.unslider').css('z-index', '0');
	});
});

correctResponsiveness();
$(window).resize(function() {
	correctResponsiveness();
});

function correctResponsiveness () {
	// correct dropdown menu position
	var intWidth = $('li.drop').width();
	$('ul.dropmenu').width(intWidth);
	var oHeight = $('li.drop').height();
	$('ul.dropmenu').css('top', oHeight);

	// vertically center headings in first column (index page)
	var imageHeight = $('div.entry-image img').height();
	var textHeight = $('div.entry-title').height();
	var marginTop = (imageHeight - textHeight) * 0.45;
	var marginBottom = (imageHeight - textHeight) * 0.55;
	$('div.entry-title').css('margin-top', marginTop);
	$('div.entry-title').css('margin-bottom', marginBottom);

	// slider for index page - corrections
	var menuHeight = $('ul.topmenu').height();
	$('body > div#main').css('margin-top', menuHeight);
}

$("a.stars").click(function(){
	$("a.stars").removeClass("unselected").removeClass("selected").removeClass("removeSelection");
	var selectedStar = $(this).attr("class").split(' ').pop();
	var rating = selectedStar.split('-');
	rating = rating[1];
	$("input#rating-value").val(rating);

	// select the star and the ones before it
	$(this).addClass("selected");
	$(this).prevAll().addClass("selected");
	$(this).nextAll().addClass("removeSelection");

	if (!$(this).hasClass("rated")) {
		// select all of the upper/lower stars too
		var selector = "";
		if ($(this).hasClass("lower")) {
			selector = "upper";
		}
		else if ($(this).hasClass("upper")) {
			selector = "lower";
		}
		selector = "a.stars."+selector+"."+selectedStar;
		$(selector).addClass("selected");
		$(selector).prevAll().addClass("selected");
		$(selector).nextAll().addClass("removeSelection");
	}

	// focus user on textarea to write comment
	var offset = $("div.comment-add form textarea").offset();
	$('html, body').animate({
	    scrollTop: offset.top,
	    scrollLeft: offset.left
	}, 1000);
	$("div.comment-add form textarea").focus();
});

$('a#delete-profile').click(function(){
	let res = confirm("Желаете ли да изтриете профила си?");
	if (res) {
		let confirmation = confirm("Това действие не може да бъде отменено. Сигурни ли сте, че искате да продължите?");
		if (confirmation) {
			const url = new URL(window.location);
			const params = new URLSearchParams(url.search);
			params.set("deleteprofile", "delete");
			window.location.replace(window.location.protocol + '//' + window.location.host + window.location.pathname + 
				"?" + params.toString());
		}
	}
});

$('a#delete-rating').click(function(){
	let res = confirm("Желаете ли да изтриете този коментар?");
	if (res) {
		const url = new URL(window.location);
		const params = new URLSearchParams(url.search);
		params.set("delete-rating", $(this).attr("data-comment"));
		window.location.replace(window.location.protocol + '//' + window.location.host + window.location.pathname + 
			"?" + params.toString());
	}
});

$('a#change-name').click(function(){
	let html = $(this).parent().html();
	html = html.split(/&nbsp;/);
	html[0] = "<form action='' method='POST'><input style='font-size: 1.2em; border-radius: 5px;' size='15' type='text' name='username' value='"+html[0];
	html[0] += "' /><button style='border: 0; margin: 0px 0px 0px 10px; background-color: white; font-size: 1.2em; text-align: center; cursor: pointer;' type='submit'><i class='fa fa-check' aria-hidden='true'></i></button></form>";
	$(this).parent().html(html[0]);
});

$('a#change-picture').click(function(){
	let html = $(this).parent().html();
	html = "<form action='' method='POST' enctype='multipart/form-data'><input style='font-size: 20px; width: 300px;' type='file' name='pic' id='pic' /><input type='hidden' name='changepic' value='1' />";
	html += "<button style='border: 0; margin: 0px 0px 0px 10px; background-color: white; font-size: 25px; text-align: center; cursor: pointer;' type='submit'><i class='fa fa-check' aria-hidden='true'></i></button></form>";
	$(this).parent().html(html);
});

$('a#enter-another-variety').click(function(){
	let html = '<input type="text" name="another-variety" id="another-variety" size="35" style="position: relative; top: -5px;" required placeholder="Други, разделени със запетая" />';
	$(this).parent().html(html);
});

$('select#winery').change(function(){
	if ($(this).val() == "other") {
		let html = '<input type="text" name="another-winery" required id="another-winery" size="25" style="position: relative; top: -2px;" placeholder="Друга винарна" />';
		$("span.enter-another.winery").html(html);
		$("tr.region").attr("style", "display: table-row;");
	}
});

$("input#photo").change(function(){
	if (this.files && this.files[0]) {
		var reader = new FileReader();
		reader.onload = function(e) {
			$("form.add-wine img.wine-main").attr('src', e.target.result);
		}
		reader.readAsDataURL(this.files[0]);
	}
});

$('select.sorting').on('change', (event) => {
	const value = event.target.value;
	const url = new URL(window.location);
	const params = new URLSearchParams(url.search);
	params.set("sortby", value);

	if (value == "added" || value == "rating" || value == "year" || value == "alc" || value == "name") {
		window.location.replace(window.location.protocol + '//' + window.location.host + window.location.pathname +
			"?" + params.toString());
	}
	});
});

function validateForm() {
	if ($("input#rating-value").val().trim().length < 1 || $("input#rating-value").val().trim() == "0") {
		alert("Моля, изберете оценка от 1 до 5!");
		return false;
	}

	if ($("div.comment-add form textarea").val().trim().length < 1) {
		alert("Моля, въведете коментар към своя отзив.");
		return false;
	}

	return true;
}

function validateAddForm() {
	if ($("input#wine-name").val().trim().length < 1) {
		alert("Моля, въведете име на виното.");
		return false;
	}

	if (!$("input#photo").val()) {
		alert("Моля, изберете снимка за виното.");
		return false;
	}

	let wineryField = $("select#winery");
	if (wineryField.length) {
		let val = $(wineryField).val().trim();
		if (val.length < 1 || (val != "other" && parseInt(val) < 1)) {
			alert("Моля, изберете винарна.");
			return false;
		}
		else {
			if (val == "other") {
				let additional = $("input#another-winery");
				if (additional.length) {
					let regionField = $("select#region");
					if (regionField.length) {
						val = $(regionField).val().trim();
						if (val.length < 1 || parseInt(val) < 1) {
							alert("Моля, изберете регион.");
							return false;
						}
					}

					if ($(additional).val().trim().length < 1) {
						alert("Моля, изберете винарна.");
						return false;
					}
				}
				else {
					alert("Моля, изберете винарна.");
					return false;
				}
			}
		}
	}

	let val = $("input#year").val().trim();
	if (val.length < 1 || parseInt(val) < 1940 || parseInt(val) > parseInt(new Date().getFullYear())) {
		alert("Моля, изберете година на производство.");
		return false;
	}

	val = $("select#cat").val().trim();
	if (val.length < 1 || (val != "RED" && val != "WHITE" && val != "ROSE")) {
		alert("Моля, изберете категория.");
		return false;
	}

	val = $("select#variety").val();
	if (val.length < 1) {
		let additional = $("input#another-variety");
		if (additional.length) {
			if ($(additional).val().trim().length < 1) {
				alert("Моля, изберете сорт.");
				return false;
			}
		}
		else {
			alert("Моля, изберете сорт.");
			return false;
		}
		
	}

	val = $("input#alc").val().trim();
	if (val.length < 1 || parseFloat(val) < 8 || parseFloat(val) > 30) {
		alert("Моля, изберете алкохолен процент.");
		return false;
	}

	val = $("form.add-wine textarea").val().trim();
	if (val.length < 1) {
		alert("Моля, изберете подходящи храни.");
		return false;
	}

	return true;
}