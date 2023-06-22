var RawGraphData="";
function DrawGraph(user, LogDate, IPAddress, API_action)
	{
	document.getElementById('loading_please_wait').style.display='block';
	document.getElementById('chart_div').style.display='none';

	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	try {
	xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
		xmlhttp = false;
		}
	}
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		var GraphQuery = "user=" + user + "&log_date=" + LogDate + "&web_ip=" + IPAddress + "&ACTION="+API_action;
		// alert(GraphQuery);
		xmlhttp.open('POST', 'dygraph_functions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(GraphQuery); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				RawGraphData = xmlhttp.responseText;

				if (API_action=="all_agent_latency" || API_action=="latency_gaps")
					{
					var LSL_value=1;
					var stacked=1;
					}
				else
					{
					var LSL_value=0;
					var stacked=0;
					}

				var graphTitle='Latency';
				if (!user.match("---ALL---"))
					{
					graphTitle+=' for agent '+user;
					}
				else
					{
					graphTitle+=' for ALL agents';
					}

				graphTitle+=' for '+LogDate;

				if (!IPAddress.match("---ALL---"))
					{
					graphTitle+=' on IP address '+IPAddress;
					}
				//var graph_data=[];
				//var maxLatency=0;
				//var graph_array=RawGraphData.split("|");
				//alert(graph_array.length);
				//for (var i=0; i<graph_array.length; i++)
				//	{
				//	dataset=graph_array[i].split(",");
				//	if (i<5) {alert(dataset[0]);}
				//	graph_data.push([dataset[0], parseInt(dataset[1])]);
				//	if (parseInt(dataset[1])>maxLatency)
				//		{
				//		maxLatency=parseInt(dataset[1]);
				//		}
				//	}
				if (!LatencyGraph)
					{
					// alert(RawGraphData);
					var LatencyGraph=new Dygraph(document.getElementById("latency_graph_div"), RawGraphData,
						{
						title: graphTitle,
						labelsDiv: document.getElementById("legend_div"),
						drawPoints: true,
						showRoller: false,
						resizable: "both",
						// legend: 'follow',
						// legendFollowOffsetX: 5,
						// legendFollowOffsetY: -5,
						labelsSeparateLines: LSL_value,
						connectSeparatedPoints: false,
						drawGapEdgePoints: false,
						ylabel: 'Latency',
						xlabel: 'Time (HH:mm:ss)',
						strokeWidth: 1.5
						} );
					}
				else
					{
					LatencyGraph.updateOptions( {'file': RawGraphData, title: graphTitle} );
					}
				
				document.getElementById('loading_please_wait').style.display='none';
				document.getElementById('chart_div').style.display='block';
				}
			}
		}



	}
