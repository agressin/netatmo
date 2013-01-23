var data;
var data_overview;
var options;
var plot;
var placeholder;
var ranges;
		ranges = [];
    ranges.xaxis = [];
var currentTime = new Date().getTime() + 60 * 60 * 1000;
    ranges.xaxis.from = date_begin;
    ranges.xaxis.to = currentTime;
var type='';
var doLissage=true;
//*****************************************************************************/
// lissage
//*****************************************************************************/
function lissage(d)
{
	var filtre = {'-2':5/49, '-1':12/49, '0':15/49, '1':12/49, '2':5/49 };
	var out = d;
	for (var i = 0; i < d.length; ++i)
	{
		var temp = 0;
		for(var j=-2; j<=2; j++)
		{
			var id = Math.max(0,i+j);
				  id = Math.min(d.length-1,id);
				  //alert(id);
    	temp += d[id][1] * filtre[j];
		}
		out[i][1] = temp;
	}
	return out;
}
//*****************************************************************************/
// helper for returning the weekends in a period
//*****************************************************************************/
function dayAreas(axes) {
    var markings = [];
    var d = new Date(axes.xaxis.min);
    // go to the first Saturday
    d.setUTCDate(d.getUTCDate() - (d.getUTCDay() + 1))
    d.setUTCSeconds(0);
    d.setUTCMinutes(0);
    d.setUTCHours(20);
    var i = d.getTime();
    do {
        // when we don't set yaxis, the rectangle automatically
        // extends to infinity upwards and downwards
        markings.push({ xaxis: { from: i, to: i + 12 * 60 * 60 * 1000 } });
        i += 24 * 60 * 60 * 1000;
    } while (i < axes.xaxis.max);

    return markings;
}
//*****************************************************************************/
// myPlot
//*****************************************************************************/
function myPlot(typeChanged)
{

	options = {
      series: { lines: { show: true }, shadowSize: 0 },
      xaxis: {  mode: "time" },
      selection: { mode: 'x' },
      grid: { markings: dayAreas }
  	};
	placeholder = $('#placeholder');
	plot = $.plot(placeholder, data, options);

	if(typeChanged)
	{
		options_overview = { series: {lines: { show: true, lineWidth: 1 }, shadowSize: 0 },
														xaxis: { ticks: [], mode: 'time' },
														yaxis: { ticks: [], autoscaleMargin: 0.1 },
														selection: { mode: 'x' }
													};
		overview = $.plot($('#overview'), data_overview, options_overview );
		overview.setSelection(ranges, true);

		$('#placeholder').bind('plotselected', function (event, r)
		{
			 ranges = r;
			 changePlot();
			 overview.setSelection(r, true);
		});


		$('#overview').bind('plotselected', function (event, ranges)
		{
			plot.setSelection(ranges);
		});
	}
}
//*****************************************************************************/
// autoScale
//*****************************************************************************/
function autoScale(begin,end)
{
	var scale="max";
	if ((end - begin)>1024*60*5) // 1 mesure / 5 min
	{
		if ((end - begin)>1024*60*30) // 1 mesure / 30 min
		{
			if ((end - begin)>1024*60*60*3) // 1 mesure / 3 hours
			{
				if ((end - begin)>1024*60*60*24) // 1 mesure / 1 day
				{
					if ((end - begin)>1024*60*60*24*7) // 1 mesure / 1 week -> 20 years ...
					{
						scale="1month";
					}
					else
						scale="1week";
				}
				else
					scale="1day";
			}
			else
				scale="3hours";
		}
		else
			scale="30min";
	}
	return scale;
}
//*****************************************************************************/
// changePlot
//*****************************************************************************/
function changePlot(type_temp)
{
	var typeChanged = false;
	if(type_temp)
	{
		if(type_temp != type)
			typeChanged = true;
		type=type_temp;
	}

	var begin = ranges.xaxis.from/1000 -60*60;
	var end = ranges.xaxis.to/1000;
	var scale = autoScale(begin,end);
	$.ajax({
		type: "POST",
		url: "ajax/getData.php",
		data: { scale: scale, type: type, begin: begin, end: end}
	}).done(function( jsonData ) {
	
		var temp = eval('(' + jsonData + ')');
		var d = eval( temp[type] );
		for (var i = 0; i < d.length; ++i)
    		d[i][0] += 60 * 60 * 1000;
    		
		var d_over = eval( temp['overview'] );
		for (var i = 0; i < d_over.length; ++i)
    	d_over[i][0] += 60 * 60 * 1000;

		if(doLissage)
			d = lissage(d);

		/*
		var d_min = eval( temp['min'] );
		var d_max = eval( temp['max'] );
		if(d_min && d_max)
		{
			for (var i = 0; i < d_min.length; ++i)
    		d_min[i][0] += 60 * 60 * 1000;
			
			for (var i = 0; i < d_max.length; ++i)
		  	d_max[i][0] += 60 * 60 * 1000;
		  	
			data = [
		     { id: 'mean', data: d, lines: { show: true } },
		     { id: 'min', data: d_min, lines: { show: true, lineWidth: 1, fill: false }, color: "rgb(255,50,50)" },
		     { id: 'max', data: d_max, lines: { show: true, lineWidth: 1, fill: true }, color: "rgb(255,50,50)", fillBetween: 'min' }
		  	];
		}
		else
		*/
		
		data = [d];
		data_overview = [d_over];
		
		$('#nbMesure').html('Il y a actuellement '+ d.length +' mesures.');
		
		myPlot(typeChanged);

	});
}

$(function() {

//*****************************************************************************/
// Pour les boutons
		$( "#radio" ).buttonset();
		$( "#radioPeriod" ).buttonset();
//*****************************************************************************/
//Pour la vue en accordéon
    $( "#accordion" ).accordion({
        collapsible: true,
        heightStyle: "content",
	active : false
    });
//*****************************************************************************/
//Pour les graph
		changePlot('Temperature');
		
//*****************************************************************************/
    $("#lissage").click(function () {
    		
        doLissage = !doLissage;
        changePlot();

    });
//*****************************************************************************/
    $("#whole").click(function () {
    		this.selected = true
        ranges.xaxis.from = date_begin;
        ranges.xaxis.to = currentTime;

        changePlot();
        overview.setSelection(ranges, true);
    });
//*****************************************************************************/
    $("#lastWeek").click(function () {

        var lastWeek = currentTime - 7*24*60*60*1000;
        ranges.xaxis.from = lastWeek;
        ranges.xaxis.to = currentTime;

				changePlot();
        overview.setSelection(ranges, true);
    });
//*****************************************************************************/
    $("#lastDay").click(function () {
        var lastDay = currentTime - 24*60*60*1000;
        ranges.xaxis.from = lastDay;
        ranges.xaxis.to = currentTime;

				changePlot();
        overview.setSelection(ranges, true);
    });
//*****************************************************************************/
    $("#lastH").click(function () {
        var lastH = currentTime - 3*60*60*1000;
        ranges.xaxis.from = lastH;
        ranges.xaxis.to = currentTime;

				changePlot();
        overview.setSelection(ranges, true);
    });


});
