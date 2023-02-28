function GoToReport(selected_report, realtime_override)
	{
	// alert(selected_report);
	if (realtime_override) {document.location.href='/vicidial/realtime_report.php'; exit;}
	document.getElementById("report_type").value=selected_report;
	document.getElementById("download_rpt").value='';
	document.forms[0].submit();
	}

function DownloadReport(selected_report, selected_subreport, sort_param, sort_value)
	{
	// alert(selected_report);
	// alert(document.getElementById("report_type").value);
	document.getElementById("report_type").value=selected_report;
	document.getElementById("download_rpt").value=selected_subreport;
	document.forms[0].submit();
	}

function GoToDetailsPage(selected_report, page_no)
	{
	// alert(document.getElementById("report_type").value);
	document.getElementById("report_type").value=selected_report;
	document.getElementById("page_no").value=page_no;
	document.forms[0].submit();
	}

function GoToCustomReport(realtime_override) 
	{
	
	var full_report_var_str="";
	if (realtime_override) {document.location.href='/vicidial/realtime_report.php'; exit;}

	var report_queues_value=$( "input[type=text][id=vicidial_queue_groups]" ).val();
	if (report_queues_value!="") 
		{
		var vicidial_queue_groups=$('#VERM_report_queues [value="' + report_queues_value + '"]').data('value');
		full_report_var_str+="&vicidial_queue_groups="+vicidial_queue_groups;
		}
	else
		{
		vicidial_queue_groups="000_MCRR_ALL_Calls";
		full_report_var_str+="&vicidial_queue_groups="+vicidial_queue_groups;
		}

	var report_types_value=$( "input[type=text][id=report_types]" ).val();
	if (report_types_value!="") 
		{
		var report_type=$('#VERM_reports [value="' + report_types_value + '"]').data('value');
		full_report_var_str+="&report_type="+report_type;
		}

	var time_period_value=$( "input[type=text][id=time_period]" ).val();
	if (time_period_value!="") 
		{
		var time_period=$('#VERM_time_period [value="' + time_period_value + '"]').data('value');
		full_report_var_str+="&time_period="+time_period;
		}

	var start_date=document.getElementById('start_date').value;
	if (start_date!="") {full_report_var_str+="&start_date="+start_date;}

	var start_time_hour=document.getElementById('start_time_hour').value;
	if (start_time_hour!="") {full_report_var_str+="&start_time_hour="+start_time_hour;}

	var start_time_min=document.getElementById('start_time_min').value;
	if (start_time_min!="") {full_report_var_str+="&start_time_min="+start_time_min;}

	var end_date=document.getElementById('end_date').value;
	if (end_date!="") {full_report_var_str+="&end_date="+end_date;}

	var end_time_hour=document.getElementById('end_time_hour').value;
	if (end_time_hour!="") {full_report_var_str+="&end_time_hour="+end_time_hour;}

	var end_time_min=document.getElementById('end_time_min').value;
	if (end_time_min!="") {full_report_var_str+="&end_time_min="+end_time_min;}

	var hourly_slot=document.getElementById('hourly_slot').value;
	if (hourly_slot!="") {full_report_var_str+="&hourly_slot="+hourly_slot;}

	var SLA_initial_period=document.getElementById('SLA_initial_period').value;
	if (SLA_initial_period!="") {full_report_var_str+="&SLA_initial_period="+SLA_initial_period;}

	var SLA_initial_interval=document.getElementById('SLA_initial_interval').value;
	if (SLA_initial_interval!="") {full_report_var_str+="&SLA_initial_interval="+SLA_initial_interval;}

	var SLA_max_period=document.getElementById('SLA_max_period').value;
	if (SLA_max_period!="") {full_report_var_str+="&SLA_max_period="+SLA_max_period;}

	var SLA_interval=document.getElementById('SLA_interval').value;
	if (SLA_interval!="") {full_report_var_str+="&SLA_interval="+SLA_interval;}

	var short_call_wait_limit=document.getElementById('short_call_wait_limit').value;
	if (short_call_wait_limit!="") {full_report_var_str+="&short_call_wait_limit="+short_call_wait_limit;}

	var short_call_talk_limit=document.getElementById('short_call_talk_limit').value;
	if (short_call_wait_limit!="") {full_report_var_str+="&short_call_talk_limit="+short_call_talk_limit;}

	var short_attempt_wait_limit=document.getElementById('short_attempt_wait_limit').value;
	if (short_attempt_wait_limit!="") {full_report_var_str+="&short_attempt_wait_limit="+short_attempt_wait_limit;}

	var users_value=$( "input[type=text][id=users]" ).val();
	if (users_value!="") 
		{
		var users=$('#agent_filter_list [value="' + users_value + '"]').data('value');
		full_report_var_str+="&users="+users;
		}

	var dialer_location_value=$( "input[type=text][id=location]" ).val();
	if (dialer_location_value!="") 
		{
		var dialer_location=$('#location_filter_list [value="' + dialer_location_value + '"]').data('value');
		full_report_var_str+="&location="+dialer_location;
		}

	var user_group_value=$( "input[type=text][id=user_group]" ).val();
	if (user_group_value!="") 
		{
		var user_group=$('#user_group_filter_list [value="' + user_group_value + '"]').data('value');
		full_report_var_str+="&user_group="+user_group;
		}

	var statuses_value=$( "input[type=text][id=statuses]" ).val();
	if (statuses_value!="") 
		{
		var statuses=$('#statuses_filter_list [value="' + statuses_value + '"]').data('value');
		full_report_var_str+="&statuses="+statuses;
		}

	var asterisk_cid=document.getElementById('asterisk_cid').value;
	if (asterisk_cid!="") {full_report_var_str+="&asterisk_cid="+asterisk_cid;}

	var phone_number=document.getElementById('phone_number').value;
	if (phone_number!="") {full_report_var_str+="&phone_number="+phone_number;}

	var wait_sec_min=document.getElementById('wait_sec_min').value;
	if (wait_sec_min!="") {full_report_var_str+="&wait_sec_min="+wait_sec_min;}

	var wait_sec_max=document.getElementById('wait_sec_max').value;
	if (wait_sec_max!="") {full_report_var_str+="&wait_sec_max="+wait_sec_max;}

	var length_in_sec_min=document.getElementById('length_in_sec_min').value;
	if (length_in_sec_min!="") {full_report_var_str+="&length_in_sec_min="+length_in_sec_min;}

	var length_in_sec_max=document.getElementById('length_in_sec_max').value;
	if (length_in_sec_max!="") {full_report_var_str+="&length_in_sec_max="+length_in_sec_max;}

	var disconnection_cause_value=$( "input[type=text][id=disconnection_cause]" ).val();
	if (disconnection_cause_value!="") 
		{
		var disconnection_cause=$('#disconnection_cause_list [value="' + disconnection_cause_value + '"]').data('value');
		full_report_var_str+="&disconnection_cause="+disconnection_cause;
		}

	var queue_position_min=document.getElementById('queue_position_min').value;
	if (queue_position_min!="") {full_report_var_str+="&queue_position_min="+queue_position_min;}

	var queue_position_max=document.getElementById('queue_position_max').value;
	if (queue_position_max!="") {full_report_var_str+="&queue_position_max="+queue_position_max;}

	var call_count_min=document.getElementById('call_count_min').value;
	if (call_count_min!="") {full_report_var_str+="&call_count_min="+call_count_min;}

	var call_count_max=document.getElementById('call_count_max').value;
	if (call_count_max!="") {full_report_var_str+="&call_count_max="+call_count_max;}

	var did_value=$( "input[type=text][id=did]" ).val();
	if (did_value!="") 
		{
		var did=$('#did_list [value="' + did_value + '"]').data('value');
		full_report_var_str+="&did="+did;
		}

	var ivr_choice=document.getElementById('ivr_choice').value;
	if (ivr_choice!="") {full_report_var_str+="&ivr_choice="+ivr_choice;}

	var server_value=$( "input[type=text][id=server]" ).val();
	if (server_value!="") 
		{
		var server=$('#server_list [value="' + server_value + '"]').data('value');
		full_report_var_str+="&server="+server;
		}

	var dow_menu = document.getElementById('dow[]');
	for (var i=0; i<dow_menu.options.length; i++) 
		{
		if (dow_menu.options[i].selected) 
			{
			full_report_var_str+="&dow[]="+dow_menu.options[i].value;
			}
		}

	// if (dow!="") {full_report_var_str+="&dow[]="+dow;}

	var time_of_day_start=document.getElementById('time_of_day_start').value;
	if (time_of_day_start!="") {full_report_var_str+="&time_of_day_start="+time_of_day_start;}

	var time_of_day_end=document.getElementById('time_of_day_start').value;
	if (time_of_day_end!="") {full_report_var_str+="&time_of_day_end="+time_of_day_end;}

	// alert(full_report_var_str);

	document.location.href=document.forms[0].action+"?"+full_report_var_str;
	}


