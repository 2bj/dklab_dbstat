var GRAPH_WIDTH = 0, GRAPH_HEIGHT = 300, GRAPH_PADDING = 10, GRAPH_BORDER = 2;
var GRAPH_COLORS = [ [ '#F00', '#FBB' ], [ '#0A0', '#9F9' ] ];
var lastClickedChk = null;
var lastCheckedChks = [];
var lastShownGraph = null;


function getValuesOfRows(trs) {
	var pos = 4;
	var $table = $(trs[0]).closest('table');
	var $headTds = $table.find('tr:first').children('td:not(.incomplete)');

	var setOfTdsList = [];
	var vAxes = [];
	var series = [];
	$.each(trs, function(i) {
		setOfTdsList.push($(this).children('td:not(.incomplete)'));
		var color = GRAPH_COLORS[i][0];
		var gridColor = GRAPH_COLORS[i][1];
		vAxes.push({
			gridlineColor: gridColor,
			textStyle: { color: color },
			viewWindowMode: 'maximized'
		});
		series.push({
			targetAxisIndex: i,
			color: color,
			lineWidth: 4 + (trs.length - i) * 2
		});
	});
	
	var data = [];
	for (var i = pos; i < $headTds.length; i++) {
		var col = [];
		col.push($($headTds[i]).html().replace(/<.*?>/g, ' '));
		$.each(setOfTdsList, function() {
			var value = ($(this[i]).attr("value")||"").replace(/[^0-9.]+/g);
			if (!value.length) value = null;
			col.push(value);
		});
		data.push(col);
	}
	
	return {
		data: data,
		vAxes: vAxes,
		series: series
	};
}


function showGraph(trs, x, yTop, yBottom, w, h) {
	var gap = GRAPH_PADDING + GRAPH_BORDER;
	if (!w) {
		w = GRAPH_WIDTH;
	} else if (w.tagName) {
		var right = $(w).offset().left + $(w).outerWidth();
		var winRight = $(window).scrollLeft() + $(window).width() - 1;
		if (right > winRight) right = winRight;
		w = right - x + 1;
	}
	if (!h) h = GRAPH_HEIGHT;
	var y = yBottom;
	if (y + h > $(window).scrollTop() + $(window).height() - 40) {
		y = yTop - h;
	}
	var $target = $('<div class="graph"></div>').appendTo(document.body).css({ 
		width: (w - gap * 2) + "px",
		height: (h - gap * 2) + "px",
		left: x + "px",
		top: y + "px",
		padding: GRAPH_PADDING + "px",
		borderWidth: GRAPH_BORDER + "px"
	});
	var values = getValuesOfRows(trs);
	
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'Date');
	$.each(trs, function(i) {
        data.addColumn('number', $(this).children('td:first').text());
	})
    data.addRows(values.data.length);
    $.each(values.data, function(i) {
    	data.setValue(i, 0, this[0]);
    	for (var n = 1; n < this.length; n++) {
        	data.setValue(i, n, parseFloat(this[n]));
        }
    });
    var chart = new google.visualization.LineChart($target[0]);
    chart.draw(data, { 
    	width: w - gap * 2, 
    	height: h - gap * 2, 
    	curveType: 'function', 
    	hAxis: { 
    		direction: -1,
    		slantedText: true,
    		maxAlternation: 1
    	},
    	series: values.series,
    	vAxes: values.vAxes,
    	interpolateNulls: true,
    	pointSize: 10,
    	legend: 'top'
    });
    return $target[0];
}


function hideGraph(graph) {
	$(graph).remove();
}


google.load('visualization', '1.0', {'packages':['corechart']});
google.setOnLoadCallback(function() {
	google.loaded = true;
});


$(function() { 
	$('textarea').autogrow();
	
	lastCheckedChks = $('.chk:checked');
	
	$('.chk').closest('td').click(function(e) { 
		if (e.target.tagName == 'INPUT') return;
		$(this).find('.chk')[0].click(e);
	});
	$('.chk').click(function(e) {
		var chk = this;
		
		if (e.shiftKey && lastClickedChk) {
			var $chks = $(".chk:visible");
			var p1 = $chks.index(lastClickedChk);
			var p2 = $chks.index(chk);
			if (p1 > p2) { tmp = p1; p1 = p2; p2 = tmp; }
			for (var i = p1; i <= p2; i++) {
				$chks[i].checked = chk.checked;
			}
		}

		lastCheckedChks = $.grep(lastCheckedChks, function(e) { return e != chk });
		
		if (google.loaded) {
			var lastCheckedChk = lastCheckedChks.length? lastCheckedChks[lastCheckedChks.length - 1] : null;
			var rowsToUse = [];
			if (chk.checked) {
				rowsToUse.push($(chk).closest('tr')[0]);
			}
			if (lastCheckedChk && lastCheckedChk != chk && lastCheckedChk.checked) {
				rowsToUse.push($(lastCheckedChk).closest('tr')[0]);
			}
			if (lastShownGraph) {
				hideGraph(lastShownGraph);
				lastShownGraph = null;
			}
			var showAtChk = chk.checked? chk : (lastCheckedChk && lastCheckedChk.checked? lastCheckedChk : null);
			if (showAtChk) {
				var $td = $(showAtChk).closest('td'); 
				var ofs = $td.offset();
				lastShownGraph = showGraph(
					rowsToUse, 
					ofs.left + $td.outerWidth(),
					ofs.top, ofs.top + $td.outerHeight(),
					$td.closest('tr')[0]
				);
			}
		}
		
		if (chk.checked) {
			$(chk).closest('tr').addClass('checked');
			lastCheckedChks.push(chk);
		} else {
			$(chk).closest('tr').removeClass('checked');
		}

		lastClickedChk = chk;
	});
});
