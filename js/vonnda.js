// JavaScript Document
var browserName=navigator.appName;var browserVer=parseInt(navigator.appVersion);var version="";var msie4=(browserName=="Microsoft Internet Explorer"&&browserVer>=4);if((browserName=="Netscape"&&browserVer>=3)||msie4||browserName=="Konqueror"||browserName=="Opera"){version="n3";}else{version="n2";}
function blurLink(theObject){if(msie4){theObject.blur();}}
function decryptCharcode(n,start,end,offset){n=n+offset;if(offset>0&&n>end){n=start+(n-end-1);}else if(offset<0&&n<start){n=end-(start-n-1);}
return String.fromCharCode(n);}
function decryptString(enc,offset){var dec="";var len=enc.length;for(var i=0;i<len;i++){var n=enc.charCodeAt(i);if(n>=0x2B&&n<=0x3A){dec+=decryptCharcode(n,0x2B,0x3A,offset);}else if(n>=0x40&&n<=0x5A){dec+=decryptCharcode(n,0x40,0x5A,offset);}else if(n>=0x61&&n<=0x7A){dec+=decryptCharcode(n,0x61,0x7A,offset);}else{dec+=enc.charAt(i);}}
return dec;}
function linkTo_UnCryptMailto(s){location.href=decryptString(s,2);}

$(document).ready(function() {
	// Background
	$(function(){
		$('#vg1').floatingBackground({
			scale : 0.1,
			xOffset : -100,
			yOffset : -720,
			blur : false,
		});
		$('#vg2').floatingBackground({
			scale : 0.2,
			xOffset : -110,
			yOffset : -780,
			blur : false,
		});
		$('#vg3').floatingBackground({
			scale : 0.3,
			xOffset : -90,
			yOffset : -740,
			blur : false,
		});
	});
	
	// Slideshow
	$(function(){     
		$('#slideshow').cycle({
			timeout: 5000,  // milliseconds between slide transitions (0 to disable auto advance)
			fx:      'fade', // choose your transition type, ex: fade, scrollUp, shuffle, etc...   
			pager:   '#pager',  // selector for element to use as pager container
			pause:   0,	  // true to enable "pause on hover"
			cleartypeNoBg: true, // set to true to disable extra cleartype fixing (leave false to force background color setting on slides)
			pauseOnPagerHover: 0 // true to pause when hovering over pager link
		});
	});
	
	// Totop Link
	$(function () { // run this code on page load (AKA DOM load)

		/* set variables locally for increased performance */
		var scroll_timer;
		var displayed = false;
		var $message = $('#totop');
		var $window = $(window);
		var top = $(document.body).children(0).position().top;

		/* react to scroll event on window */
		$window.scroll(function () {
			window.clearTimeout(scroll_timer);
			scroll_timer = window.setTimeout(function () { // use a timer for performance
				if($window.scrollTop() <= top) // hide if at the top of the page
				{
					displayed = false;
					$message.fadeOut(500);
				}
				else if(displayed == false) // show if scrolling down
				{
					displayed = true;
					$message.stop(true, true).show().click(function () { $message.fadeOut(500); });
				}
			}, 100);
		});
	});
	
	// To Top Button
    $('a[href=#top]').click(function(){
        $('html, body').animate({scrollTop:0}, 'slow');
        return false;
    });

	// Portfolio Peep Show
	$('.peepshow').hover(
	function(){
		$(".cover", this).stop().animate({top:'0px'},{queue:false,duration:160});
		$(".launch", this).stop().animate({right:'0px'},{queue:false,duration:160});
	}, function() {
		$(".cover", this).stop().animate({top:'-230px'},{queue:false,duration:160});
		$(".launch", this).stop().animate({right:'-150px'},{queue:false,duration:160});
	});
	
	// Equal Heights
	$(".eqH").equalHeights(120,300);
	
	// Hides cForm
	$('#c253').hide();

	// Toggles cForm
	$('#cform-toggle').click(function() {
		$('#c253').slideToggle(400);
		return false;
	});
	
	// Validate cForm
	$("#questionnare").validate();
	
	
});