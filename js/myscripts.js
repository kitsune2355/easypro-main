// JavaScript Document
function resizescreen(){
	
	var pantalla_ancho = $(window).width();
	
	if((pantalla_ancho > 979)&&(pantalla_ancho < 1221)){
		
		$("#page").css('width', 942);
		$(".col.cone, .col.ctwo, .col.cthree, .col.cfour").hide();

	}else if((pantalla_ancho > 1221)&&(pantalla_ancho < 1462)){
		
		$("#page").css('width', 1182);
		$(".col.cone, .col.ctwo, .col.cthree, .col.cfour").hide();
		$(".col.cone").show();

	}else if(pantalla_ancho > 1462){
		
		$("#page").css('width', 1423);
		$("#top .col").show();

	}
	
}

$(window).resize(function () {

	resizescreen();

});

$(document).ready(function() {
	
	resizescreen();
	
/* GALLERY */

	 var altofotogallery = $("#thegallery .picture img").height();
	 var margenthumb = (altofotogallery/2)-95;
	 $("#thegallery .previous, #thegallery .next").css('margin-bottom', ''+margenthumb+'px');

/* RANKING HOME */

	$(".rankinghome .more").hide();
	
	$(".rankinghome .item").mousedown(function() {

		if(this.className.indexOf("active") != -1) {
			$(this).next().slideUp(200);
			$(this).removeClass("active");
		}
		else {
			$(".rankinghome .item").removeClass("active");
			$(this).addClass("active");
			$(".rankinghome .item").next().slideUp(200);
			$(this).next().slideDown(200);
		}
		return false;
	});
	
	$(".more .close").click(function() {
			$(".rankinghome .item").removeClass("active");
			$(this).addClass("active");
			$(".rankinghome .item").next().slideUp(200);
			$(this).next().slideDown(200);
		return false;
	});
	
/* OPEN ABOUT US */

	$('.openaboutus').click(function(){	
		$('#aboutus').css('display','block');	
		$('#header').animate({ marginTop: "-100px" }, 1000 );
	});
	
	$('.closeaboutus').click(function(){
		$('#header').animate({ marginTop: "-470px" }, 1000 );
		setTimeout("$('#aboutus').css('display','none');",2000);
	});
	
/* TABS ABOUT US */

	$(".tab_content").hide();
	$("ul.tabs li:first").addClass("active").show();
	$(".tab_content:first").show();

	$("ul.tabs li").mousedown(function(){
								   
		$("ul.tabs li").removeClass("active");
		$(this).addClass("active");
		$(".tab_content").hide();
	
		var activeTab = $(this).find("a").attr("href");
		$(activeTab).fadeIn();
		
		return false;
		
	});
	
/* ROLLOVERS */

	// Site of the day/month
	$(".topsite .screenshot").hover(function(){
		$(this).find("img").fadeTo("fast", 0.7);
		$(this).append('<span class="viewdetails">View details</span>');
	},
	function(){
		$(this).find("img").fadeTo("fast", 1);
		$(this).find('.viewdetails').remove();
	});
	
	// Site Mention
	$(".topsite .screenshot.mention").hover(function(){
		$(this).find("img").fadeTo("fast", 0.7);
		$(this).append('<span class="viewdetails">View website</span>');
	},
	function(){
		$(this).find("img").fadeTo("fast", 1);
		$(this).find('.viewdetails').remove();
	});

	// Category blog
	$(".listsites.category li").hover(function(){
		$(this).find("a span.cat").addClass("active");
		$(this).find("img").fadeTo("fast", 0.5);
	},
	function(){
		$(this).find("a span.cat").removeClass("active");
		$(this).find("img").fadeTo("fast", 1);
	});
	
	// Awards
	$(".listsites.awards li").hover(function(){
		$(this).find("img").fadeTo("fast", 0.2);
		$(this).find(".info").fadeIn("fast");
		$(this).find(".website").fadeIn("fast");
	},
	function(){
		$(this).find("img").fadeTo("fast", 1);
		$(this).find(".info").fadeOut("fast");
		$(this).find(".website").fadeOut("fast");;
	});
	
	// Portfolio
	$(".listsites.portfolio li .screenshot").hover(function(){
		$(this).find("img").fadeTo("fast", 0.1);
		$(this).append('<span class="viewdetails">View project</span>');
	},
	function(){
		$(this).find("img").fadeTo("fast", 1);
		$(this).find('.viewdetails').remove();
	});
	
	// Post blog
	$(".listpost li .screenshot a").hover(function(){
		$(this).find("img").fadeTo("fast", 0.3);
		$(this).append('<span class="viewdetails">View</span>');
		var altofoto = $(this).find("img").height();
		$(this).find('.viewdetails').css("top", altofoto/2);
	},
	function(){
		$(this).find("img").fadeTo("fast", 1);
		$(this).find('.viewdetails').remove();
	});
	
	// Winner photos
	$("#sliderwinner").hover(function(){
		$(this).find("img").fadeTo("fast", 0.7);
		$("#sliderwinner .fav").css("display", "block");
		$(this).find("li a").append('<span class="viewdetails">View project</span>');
	},
	function(){
		$(this).find("img").fadeTo("fast", 1);
		$("#sliderwinner .fav").css("display", "none");
		$(this).find('.viewdetails').remove();
	});
	
	// Blog
	$(".listblog li").hover(function(){
		$(this).find("img").fadeTo("fast", 0.5);
	},
	function(){
		$(this).find("img").fadeTo("fast", 1);
	});
	
/* SECUENCE LIST */
/*	
	$(".listsites li").hide();
	
	var i = 0
	var n = $(".listsites li").length;
	
	function secuencesites() {
		if(i <= n){ 
			$(".listsites li").eq(i).fadeIn(100, function(){
				i++;
				secuencesites();
			});
		}		 
	} secuencesites();
*/
	
/* GOT TO TOP */
	
$(function(){
		   
	$.fn.scrollToTop=function(){
		
		$("#toTop").hide().removeAttr("href");
		$("#toFavourites").hide();
		
		if($(window).scrollTop()!="0"){
			$(this).fadeIn("slow")
		}
		var scrollDiv=$(this);
		
		$(window).scroll(function(){
								  
			if($(window).scrollTop()=="0"){
				$(scrollDiv).fadeOut("slow")
			}else{
				$(scrollDiv).fadeIn("slow")
			}
		
		});
		
		$(this).click(function(){
			$("html, body").animate({scrollTop:0},"slow");
		});
	
	}
			
});

$(function() {
	$("#toTop, #toFavourites").scrollToTop();
});
	
/* FOCUS INPUT */
	
	$('input.focus').focus(function() {
		if (this.value == this.defaultValue){ 
			this.value = '';
		}
		if(this.value != this.defaultValue){
			this.select();
		}
	});
	$('input.focus').blur(function() {
		if ($.trim(this.value) == ''){

			this.value = (this.defaultValue ? this.defaultValue : '');
		}
	});

/* HEIGHT COLUMNS BLOG */

	var height_content = $('#content').height();
	
	/* clear both */
	
	$('.listpost li:nth-child(2n+2)').after('<div class="clear"></div>');
	
	/* Caja sidebar productos */
	
	var inside_ancho = $('.inside').width();
	
	if(inside_ancho < 1182){

		$("#otherproducts").css('width', 0);

	}else if((inside_ancho > 1181)&&(inside_ancho < 1423)){
		
		$("#otherproducts").css('width', 218);

	}else if(inside_ancho > 1422){
		
		$("#otherproducts").css('width', 459);

	}

});


function ajustaVentana() { 

	var ancho_content = $("#page").width();
	if((ancho_content > 941)&&(ancho_content < 1181)){
	  var ancho_li = 459;
	  var alto_li = 287;
	}else if((ancho_content > 1181)&&(ancho_content < 1422)){
		 var  ancho_li = 378;
		  var alto_li = 236;
	}else if(ancho_content > 1422){
		var	ancho_li = 338;
		var	alto_li = 211;
	}

	$(".listsites.awards.view2 li img, .listsites.awards.view2 li .info").css('width', ancho_li+'px');
	$(".listsites.awards.view2 li img, .listsites.awards.view2 li .info").css('height', alto_li+'px');
	
	$(".listsites.awards.view2 li").css('width', ancho_li+'px');
	$(".listsites.awards.view2 li").css('height', alto_li+50+'px');
	
	$(".listsites.awards.view2 li .website").css('top', alto_li-30+'px');

}

$(window).resize(function() {

	ajustaVentana();

});

$(document).ready(function() {
						   
	ajustaVentana();	

}); 