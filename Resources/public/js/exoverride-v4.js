(function () {
    'use strict';

    var radarChartData = {};
    var ctx;
    var canvas;
    var userdata;
    var resourcedata;
    var widgetId;
    var radarcontent;

    //for each of the widget
    $('.radarcontent').each(function(index){
        widgetId = $(this).data('widget-instance');

        radarcontent = $(this);
        if (checkRadarAccess(widgetId))
        {
            $(radarcontent).hide();     // !!!
            $(radarcontent).show();

console.log('++widgetId'+widgetId);
console.log($(radarcontent));

            //show the list of statistic results
            $('#exo-statistics-results'+widgetId).on('click', function(){
                var that = $(this);
                $.ajax({
                    type:"GET",
                    url: Routing.generate('ujm_paper_show_all_results_v4', {resourcedata: resourcedata}),
                    success: function(response) {
                        $('#containerradardata'+widgetId).html(response);
                        that.attr('target', '_blank');
                        console.log("blank");
                    },
                    error: function(jqXHR, textStatus, errorThrown) { }
                });
            });

            ///Get the data and Draw the chart
            $('#showcsv'+widgetId).on('click', function(){
                $.ajax({
                    type:"GET",
                    url: Routing.generate('ujm_paper_export_all_results_json_v4', {userdata:userdata, resourcedata:resourcedata}),
                    success: function(response) {
                        radarChartData = response;
                        setRadarChart(radarChartData, widgetId);
                        $('.exportgraph').show();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                    }
                });
            });
        }
        else
        {
            $(radarcontent).hide();
            $('#radarmessage'+widgetId).html('Choose users and exercise in the widget parameters <span class="fa fa-pencil"></span> to display the graphics');
        }
    });

    function checkRadarAccess(widgetId)
    {
        //parameters to be sent to Chart

        userdata        = $('#userresource'+widgetId).data('user');
        resourcedata    = $('#userresource'+widgetId).data('resource').replace(/(^")|("$)/g, "");
console.log('widgetId'+widgetId);
console.log('userdata'+userdata);
console.log('resourcedata'+resourcedata);

        if (userdata != '' && resourcedata != '')
        {
            return true;
        }
        return false;
    }

    //function for creating custom tooltip for datasets
    function annotateAllX(area,ctx,data,statData,posi,posj,othervars) {
        var retstring='<B><U>'+statData[posi][posj].v2+'</U></B><BR>';
        for(var i=data.datasets.length-1;i>=0;i--) {
            var boxLegend="<canvas id=\"canvas_Line"+posi+"_"+posj+"\" height=\"10\" width=\"30\" style=\"border:1px solid black;background:"+data.datasets[i].pointHighlightFill+"\">;</canvas>";
            retstring=retstring+boxLegend+" "+statData[i][posj].datavalue+" "+statData[i][posj].v1+"<BR>";
        }
        return "<%='"+retstring+"'%>".replace(/<BR>/g," ");
    }

    function setRadarChart(radarChartData, widgetId) {
        var options = {
            canvasBorders : false
            ,canvasBordersWidth : 3
            ,canvasBordersColor : "black"
            ,legend : true
            ,inGraphDataShow : true
            ,annotateDisplay : true
            ,annotateLabel: annotateAllX
            ,responsive: true
        };
        var myRadar = new Chart(
            document.getElementById('radaranalytics'+widgetId)
            .getContext("2d")
        ).Radar(radarChartData, options);
    }

    $('#exportgraph'+widgetId).on('click', function(){
        downloadCanvas(this, 'radaranalytics'+widgetId, 'radar.png');
    });

    function downloadCanvas(link, canvasId, filename) {
        link.href = document.getElementById(canvasId).toDataURL();
        link.download = filename;/**/
    }

}());