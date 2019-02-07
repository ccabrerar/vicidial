/*
Vicidial chart functions - put any and all Javascript functions
dealing with charts/graphs for Vicidial here.
var mainChart=null;
function drawChart(chart_type, graph_id, dataset_name, graph_name){
	var canvasID="GraphDisplay"+graph_id;
	var graphName="GraphID"+graph_id;

    if(eval(graphName)==null){
		// do nothing
	} else {
		graphName.destroy();
		// main_data.type = chart_type;
		// alert(main_data.type);
		//mainChart.type = chart_type; // Would update the first dataset's value of 'March' to be 50
		//mainChart.update();
		//alert(mainChart.type);

	}
	dataset_name.type = chart_type;
	dataset_name.data.datasets.forEach(function(dataset) {
		dataset.type = chart_type
	});

	var main_ctx = document.getElementById(canvasID).getContext("2d");
	eval(graphName) = new Chart(main_ctx, dataset_name);
}
*/

function ToggleSpan(span_family, selected_span, span_count) {
	if (!span_count || span_count<1) {
		return false;
	} else {
		for (var j=0; j<span_count; j++) {
			var spanID="SpanID"+span_family+"_"+j;
			var linkID="LinkID"+span_family+"_"+j;
			if (j==selected_span || j==span_count) {
				document.getElementById(spanID).style.display = 'block';
				document.getElementById(linkID).className='thgraph selected_grey_graph_cell';
			} else {
				document.getElementById(spanID).style.display = 'none';
				document.getElementById(linkID).className='thgraph grey_graph_cell';
			}	
		}
	}
}

var main_ctx='';


Number.prototype.toHHMMSS = function () {
	var sec_num = parseInt(this, 10); // don't forget the second param
	var hours   = Math.floor(sec_num / 3600);
	var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
	var seconds = sec_num - (hours * 3600) - (minutes * 60);

	if (hours > 0) {
		if (hours   < 10) {hours   = "0"+hours;}
		hours=hours+':';
	} else {
		hours='';
	}
	if (minutes < 10) {minutes = "0"+minutes;}
	if (seconds < 10) {seconds = "0"+seconds;}
	return hours+minutes+':'+seconds;
}
/*

function prepChart(graph_type, graph_id, graph_count, dataset_name){
	if (graph_type!="<?php echo $allowed_graph; ?>" && "<?php echo $allowed_graph; ?>"!="ALL") {alert("This graph type is not allowed."); return false;}
	var canvasID="CanvasID"+graph_id+"_"+graph_count;
	var graphID="GraphID"+graph_id+"_"+graph_count;

	dataset_name.type = graph_type;
	dataset_name.data.datasets.forEach(function(dataset) {
		dataset.type = graph_type
	});

	main_ctx = document.getElementById(canvasID).getContext("2d");
}
*/