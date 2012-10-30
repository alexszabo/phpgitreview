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
	
	var j_commits_20plus = $('.commitsblock .commits li.commit:not(.selected)').slice(20);
	j_commits_20plus.hide();
	$('#showmorecommits').click(function(e){
		e.preventDefault();
		j_commits_20plus.toggle();	
	});
	
	$('.codeblock').each(function(){
		var jlines = $(this).find('.codeline');
		if (jlines.size() < 15+20+15) return;
		
		var continuous_ok = 0;
		var size = jlines.size()
		for(var i=0; i<=size; i++) {
			if (i<size) {
				var jline = jlines.eq(i);
				if (jline.hasClass('ok')) {
					continuous_ok++;
					continue;	
				}
			}
			if (continuous_ok > 15+20+15) {
				for(var j=i-continuous_ok+15; j<i-15; j++) {
					jlines.eq(j).addClass('collapsable');
				}
			}
			continuous_ok = 0;
		}
		
		var continuous_unchanged = 0;
		var size = jlines.size()
		for(var i=0; i<=size; i++) {
			if (i<size) {
				var jline = jlines.eq(i);
				if ((!jline.hasClass('added')) && (!jline.hasClass('deleted')) && (!jline.hasClass('ok'))) {
					continuous_unchanged++;
					continue;	
				}
			}
			if (continuous_unchanged > 15+20+15) {
				for(var j=i-continuous_unchanged+15; j<i-15; j++) {
					jlines.eq(j).addClass('collapsable');
				}
			}
			continuous_unchanged = 0;
		}		
	});
	
	$('.codeblock .codeline:not(.collapsable) + .codeline.collapsable')
		.before('<div class="codeline collapsehandle collapsed">...</div>');
	$('.codeblock .codeline.collapsable').hide();
	$('.collapsehandle').each(function(){
		if ($(this).next().hasClass('ok')) {
			$(this).addclass('ok')
		}
	});
		
		
	$('body')
	.delegate('.collapsehandle', 'click', function(e){
		if (e.isDefaultPrevented()) return;
		e.preventDefault();
		var jnext = $(this).next('.codeline.collapsable');
		if ($(this).hasClass('collapsed')) {
			$(this).removeClass('collapsed');
			while (jnext.size() > 0) {
				jnext.show();
				jnext = jnext.next('.codeline.collapsable');
			}
		} else {
			$(this).addClass('collapsed');
			while (jnext.size() > 0) {
				jnext.hide();
				jnext = jnext.next('.codeline.collapsable');
			}
		}
	})
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
		var jcodeblock = $(this).closest('div.codeblock');
		
		var lines = getLines($(this).attr('data-lines'));
		for(var i=0; i<lines.length; i++) {
			jcodeblock.find(".codeline[data-lineno='"+lines[i]+"']").addClass('marked');
		}
		
		var oldlines = getLines($(this).attr('data-oldlines'));
		for(var i=0; i<oldlines.length; i++) {
			jcodeblock.find(".codeline[data-oldlineno='"+oldlines[i]+"']").addClass('marked');
		}
		
	})
	;
});