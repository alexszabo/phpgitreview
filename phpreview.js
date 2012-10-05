$(function(){
	
	var getLines = function(linesstring) {
		var list = [];
		if (linesstring) {
			parts = linesstring.split(',');
			for(var i=0; i<parts.length; i++) {
				var part = parts[i];
				var num = part.split('-');
				if (num.length == 2) {
					for(var j=$.trim(num[0]); j<=$.trim(num[1]); j++) {
						list.push(j+'');
					}		
				} else {
					list.push($.trim(num[0]));
				}
			}
		}
		return list;
	} 
		
	$('body')
	.delegate('.fileblock .codeline', 'click', function(e){
		if (e.isDefaultPrevented()) return;
		e.preventDefault();
		$(this).toggleClass('selected');	
	})
	.delegate('.fileblock', 'click', function(e){
		if (e.isDefaultPrevented()) return;
		
		e.preventDefault();
		$(this).find('.codeline.selected').removeClass('selected');
		$('.marked').removeClass('marked');	
	})
	.delegate('.comments', 'click', function(e){
		$('.marked').removeClass('marked');
		if (e.isDefaultPrevented()) return;
		e.preventDefault();
		$(this).addClass('marked');
		
		var lines = getLines($(this).attr('data-lines'));
		for(var i=0; i<lines.length; i++) {
			$(".codeline[data-lineno='"+lines[i]+"']").addClass('marked');
		}
		
		var oldlines = getLines($(this).attr('data-oldlines'));
		for(var i=0; i<oldlines.length; i++) {
			$(".codeline[data-oldlineno='"+oldlines[i]+"']").addClass('marked');
		}
		
	})
	;
});