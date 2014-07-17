var count = parseInt(data.total_faders);
	
$(document).ready(function() {	
	var i = 0;
	var hoverTimeout, loopTimeout;
	var faderTimeout = setTimeout(function(){fadeInImage(0, 1, "ini");}, 6000);

	//console.log("Count " + count);
	$('.homefade-pointer-li').click(function(event) {
		event.preventDefault();
		event.stopPropagation();
		
		var id = $(this).attr("id").split('-')[1];

		clearTimeouts();
		fadeInImage(i, id, "click");
	});
	
	$("#homefade-wrap").hover(function(event) {
		clearTimeouts();
		}, function(event) {
		hoverTimeout = setTimeout(function(){fadeInImage((i < count) ? i : (i = 0), (i < count-1) ? ++i : i = 1, "hover");}, 6000);
	});	

	function fadeInImage(id, nextId, string) {
		//console.log(id + " " + nextId + ": " + string);
		$('#homefade-pic-' + id).hide();
		$('#homefade-head-' + id).hide();
		$('#homefade-description-' + id).hide();
		$('#learnmore-' + id).hide();
		$('.homefade-pointer-li').each(function() {
			$(this).removeClass("active-pointer");
		});
		
		$('#homefade-pic-' + nextId).fadeIn(800);
		$('#homefade-head-' + nextId).fadeIn(800);
		$('#homefade-description-' + nextId).fadeIn(800);
		$('#learnmore-' + nextId).fadeIn(800);
		$('#pointer-' + nextId).addClass("active-pointer");
		
		i = nextId;	
		clearTimeouts();
		loopTimeout = setTimeout(function(){fadeInImage(i, (i < count-1) ? ++i : i = 0, "loop");}, 6000);	
	}

	function clearTimeouts() {
		if (faderTimeout) {
			clearTimeout(faderTimeout); 
		}
		if (hoverTimeout) {
			clearTimeout(hoverTimeout); 
		}
		if (loopTimeout) {
			clearTimeout(loopTimeout); 
		}
	}
});

